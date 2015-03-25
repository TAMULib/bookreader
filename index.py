from Queue import Queue
import os, threading, datetime, re, httplib
from collections import defaultdict
from urllib import urlopen
from time import sleep, time
from lxml.etree import Element, tostring
from unicodedata import normalize

def add_field(doc, name, value):
    field = Element("field", name=name)
    field.text = normalize('NFC', unicode(value))
    doc.append(field)

solr_host = 'localhost:8983'

item_queue = Queue(maxsize=10000)
item_and_host_queue = Queue(maxsize=10000)
host_queues = defaultdict(lambda: Queue())
host_threads = {}
solr_queue = Queue(maxsize=10000)
counter_lock = threading.Lock()
items_processed = 0
input_counter_lock = threading.Lock()
input_count = 0
total = 1899481
current_book_lock = threading.Lock()
current_book = None
solr_ia_status_lock = threading.Lock()
solr_ia_status = None

def add_to_item_queue():
    global input_count
    skip = None
    for line in open('./list'):
        input_counter_lock.acquire()
        input_count += 1
        input_counter_lock.release()
        ia = line[:-1]
	sleep(1)
        if skip:
            if ia == skip:
                skip = None
            continue
        current_book_lock.acquire()
        current_book = ia
        current_book_lock.release()
        item_queue.put(ia)

class FindItemError(Exception):
    pass

def run_find_item():
    while True:
        ia = item_queue.get()
	
	host = 'local.' + ia
	path = '/mnt/yearbooks'
        
        item_and_host_queue.put((ia, host, path))
        item_queue.task_done()

def run_queues():
    live = dict((t.name, t) for t in threading.enumerate())

    for host, queue in host_queues.items():

        if host in live and live[host].is_alive():
            continue

        ia, filename = queue.pop()
        t = threading.Thread(name=host, target=read_text_from_node, args=(ia, host, filename))
        t.start()
        print >> log, ('thread started', host, ia)
        log.flush()
        if not queue:
            del host_queues[host]

def read_text_from_node(host):

    global items_processed
    while True:

        ia, filename = host_queues[host].get()
		
	url = 'http://localhost/BookReader/abbyy_to_text.php?ia=' + ia + '&path=/mnt/yearbooks/' + ia +  '&file=' + ia + '_abbyy.gz'
	
        reply = urlopen(url).read()
        
	if not reply:
            host_queues[host].task_done()
            continue
       

        solr_queue.put((ia, reply, 1)) #reply = body, 1 = page_count
        counter_lock.acquire()
        items_processed += 1
        counter_lock.release()
        host_queues[host].task_done()

def index_items():
    while True:
        (ia, host, path) = item_and_host_queue.get()

	filename = ia;
        
        if not filename:
            item_and_host_queue.task_done()
            continue

        host_queues[host].put((ia, path + '/' + filename))
        if host not in host_threads:
            t = threading.Thread(name=host, target=read_text_from_node, args=(host,))
            host_threads[host] = t
            t.start()
        
	item_and_host_queue.task_done()

def build_doc(ia, body, page_count):

    doc = Element('doc')
    add_field(doc, 'ia', ia)
    add_field(doc, 'id', ia)
    add_field(doc, 'body', body.decode('utf-8'))
    add_field(doc, 'body_length', len(body))
    add_field(doc, 'page_count', page_count)

    return doc

def run_solr_queue():
    h1 = httplib.HTTPConnection(solr_host)
    h1.connect()
    while True:
        (ia, body, page_count) = solr_queue.get()
        add = Element("add")
        doc = build_doc(ia, body, page_count)
        add.append(doc)
        r = tostring(add).encode('utf-8')
        url = 'http://%s/solr/update?commit=true' % solr_host

        h1.request('POST', url, r, { 'Content-type': 'text/xml;charset=utf-8'})
        response = h1.getresponse()
        response_body = response.read()

        assert response.reason == 'OK'
        solr_ia_status_lock.acquire()
        solr_ia_status = ia
        solr_ia_status_lock.release()
        solr_queue.task_done()

t0 = time()

def status_thread():
    sleep(1)
    while True:
        
	print 'input count:      %8d' % input_count
        
        print 'items processed:  %8d' % items_processed
        
        print
	sleep(1)
        
	if input_count == items_processed:
	    print 'input count:      %8d' % input_count
	    print 'items processed:  %8d' % items_processed
	    os._exit(0)

t1 = threading.Thread(target=add_to_item_queue)
t1.start()
t2 = threading.Thread(target=run_find_item)
t2.start()
t3 = threading.Thread(target=index_items)
t3.start()
t_solr1 = threading.Thread(target=run_solr_queue)
t_solr1.start()
t_solr2 = threading.Thread(target=run_solr_queue)
t_solr2.start()
t5 = threading.Thread(target=status_thread)
t5.start()

item_queue.join()
item_and_host_queue.join()
for host, host_queue in host_queues.items():
    host_queue.join()
solr_queue.join()
