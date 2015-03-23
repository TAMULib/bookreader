from Queue import Queue
import os, threading, datetime, re, httplib
from collections import defaultdict
from socket import socket, AF_INET, SOCK_DGRAM, SOL_UDP, SO_BROADCAST, timeout
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
    for line in open('/mnt/yearbooks/list'):
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

#nl_page_count = 'page count: '
def read_text_from_node(host):

    global items_processed
    while True:

        ia, filename = host_queues[host].get()

	
	#print ia
	#print ia[0:ia.find("_")]
		
	url = 'http://localhost/BookReader/abbyy_to_text.php?ia=' + ia + '&path=/mnt/yearbooks/' + ia +  '&file=' + ia + '_abbyy.gz'
	#url = 'http://localhost/BookReader/abbyy_to_text.php?ia=' + ia[0:ia.find("_")] + '&path=/mnt/yearbooks/' + ia[0:ia.find("_")] +  '&file=' + ia

        reply = urlopen(url).read()
        print url
        if not reply:
            host_queues[host].task_done()
            continue
       

	#print nl_page_count + '################################################################################'
	

	#index = reply.rfind(nl_page_count)
        	

        #last_nl = reply.rfind('\n')
        #assert last_nl != -1
        #body = reply[:index].decode('utf-8')
        #assert reply[-1] == '\n'
        #page_count = reply[index+len(nl_page_count):-1]



	#print '************************* SOLR ITEM  ******************************'
	#print ' ????? ' + page_count + '####################################################################################'
	#print '************************* SOLR ITEM  ******************************'
	
        #if not page_count.isdigit():
        #    print url


	#print '************************* SOLR ASSERT ******************************'

        #assert page_count.isdigit()


	#print '************************* SOLR ASSERT ******************************'
	#print '************************* SOLR QUEUE ******************************'

        solr_queue.put((ia, reply, 1)) #reply = body, 1 = page_count
        counter_lock.acquire()
        items_processed += 1
        counter_lock.release()
        host_queues[host].task_done()

#print 'test'
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
	    #if t.isAlive():
	    print 'thread started!'
        item_and_host_queue.task_done()

def build_doc(ia, body, page_count):

    #print '************************************************ BUILD DOCUMENT ***********************************************'

    #print ia
    #print 'BODY'
    #print 'PAGE COUNT = %d' % page_count

    doc = Element('doc')

    #print 'add ia to doc'
    add_field(doc, 'ia', ia)
    #print 'add id to doc'
    add_field(doc, 'id', ia)
    #print 'add body to doc'
    add_field(doc, 'body', body.decode('utf-8'))
    #print 'add body_length to doc'
    add_field(doc, 'body_length', len(body))
    #print 'add page_count to the doc'
    add_field(doc, 'page_count', page_count)
    #print 'done adding to the doc'
    
    #print '************************************************ DOCUMENT BUILT ***********************************************'
    #print doc.tag

    #[ child.tag for child in doc.iterchildren() ]

    #for node in doc:
    #    print node

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

	#print '################################################# SOLR REQUEST URL ####################################################'
	#print url

        h1.request('POST', url, r, { 'Content-type': 'text/xml;charset=utf-8'})
        response = h1.getresponse()
        response_body = response.read()

	#print '################################################# SOLR RESPONSE ####################################################'
	#print response_body

        assert response.reason == 'OK'
        solr_ia_status_lock.acquire()
        solr_ia_status = ia
        solr_ia_status_lock.release()
        solr_queue.task_done()

t0 = time()

def status_thread():
    sleep(1)
    while True:
        #run_time = time() - t0
        #print 'run time:         %8.2f minutes' % (float(run_time) / 60)
        #print 'input queue:      %8d' % item_queue.qsize()
        #print 'after find_item:  %8d' % item_and_host_queue.qsize()
        #print 'solr queue:       %8d' % solr_queue.qsize()

        #input_counter_lock.acquire()
        #rec_per_sec = float(input_count) / run_time
        #remain = total - input_count
        #input_counter_lock.release()

        #sec_left = remain / rec_per_sec
        #hours_left = float(sec_left) / (60 * 60)
        #print 'input count:      %8d (%.2f items/second)' % (input_count, rec_per_sec)
	print 'input count:      %8d' % input_count
        #print '                  %8.2f hours left (%.1f days/left)' % (hours_left, hours_left / 24)

        #counter_lock.acquire()
        #print 'items processed:  %8d (%.2f items/second)' % (items_processed, float(items_processed) / run_time)
        print 'items processed:  %8d' % items_processed
        #counter_lock.release()
        #current_book_lock.acquire()
        #print 'current book:', current_book
        #current_book_lock.release()
        #solr_ia_status_lock.acquire()
        #print 'most recently feed to solr:', solr_ia_status
        #solr_ia_status_lock.release()

        #host_count = 0
        #queued_items = 0
        #for host, host_queue in host_queues.items():
        #    if not host_queue.empty():
        #        host_count += 1
        #    qsize = host_queue.qsize()
        #    queued_items += qsize
        #print 'host queues:      %8d' % host_count
        #print 'items queued:     %8d' % queued_items
        print
	sleep(1)
        #if run_time < 120:
        #    sleep(1)
        #else:
        #    sleep(5)

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
