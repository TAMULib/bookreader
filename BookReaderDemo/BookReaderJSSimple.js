//
// This file shows the minimum you need to provide to BookReader to display a book
//
// Copyright(c)2008-2009 Internet Archive. Software license AGPL version 3.

// Create the BookReader object
function instantiateBookReader(selector, extraOptions) {
  selector = selector || '#BookReader';
  extraOptions = extraOptions || {};
  var options = {
    ppi: 100,
    data: [
      [
        { width: 800, height: 1200,
          uri: '//archive.org/download/BookReader/img/page001.jpg' },
      ],
      [
        { width: 800, height: 1200,
          uri: '//archive.org/download/BookReader/img/page002.jpg' },
        { width: 800, height: 1200,
          uri: '//archive.org/download/BookReader/img/page003.jpg' },
      ],
      [
        { width: 800, height: 1200,
          uri: '//archive.org/download/BookReader/img/page004.jpg' },
        { width: 800, height: 1200,
          uri: '//archive.org/download/BookReader/img/page005.jpg' },
      ]
    ],

    // Book title and the URL used for the book title link
    bookTitle: 'BookReader Demo',
    bookUrl: '../index.html',
    bookUrlText: 'Back to Demos',
    bookUrlTitle: 'This is the book URL title',

    // thumbnail is optional, but it is used in the info dialog
    thumbnail: '//archive.org/download/BookReader/img/page014.jpg',
    // Metadata is optional, but it is used in the info dialog
    metadata: [
      {label: 'Title', value: 'Open Library BookReader Presentation'},
      {label: 'Author', value: 'Internet Archive'},
      {label: 'Demo Info', value: 'This demo shows how one could use BookReader with their own content.'},
    ],

    // Override the path used to find UI images
    imagesBaseURL: '../BookReader/images/',

    ui: 'full', // embed, full (responsive)

    el: selector,
  };
  $.extend(options, extraOptions);
  var br = new BookReader(options);
  br.init();
}
<<<<<<< HEAD

// Return the height of a given page.  Here we assume all images are 1200 pixels high
br.getPageHeight = function(index) {
    return 1200;
}

// We load the images from archive.org -- you can modify this function to retrieve images
// using a different URL structure
br.getPageURI = function(index, reduce, rotate) {
    // reduce and rotate are ignored in this simple implementation, but we
    // could e.g. look at reduce and load images from a different directory
    // or pass the information to an image server
    var leafStr = '000';            
    var imgStr = (index+1).toString();
    var re = new RegExp("0{"+imgStr.length+"}$");
    var url = 'http://www.archive.org/download/BookReader/img/page'+leafStr.replace(re, imgStr) + '.jpg';
    return url;
}

// Return which side, left or right, that a given page should be displayed on
br.getPageSide = function(index) {
    if (0 == (index & 0x1)) {
        return 'R';
    } else {
        return 'L';
    }
}

// This function returns the left and right indices for the user-visible
// spread that contains the given index.  The return values may be
// null if there is no facing page or the index is invalid.
br.getSpreadIndices = function(pindex) {   
    var spreadIndices = [null, null]; 
    if ('rl' == this.pageProgression) {
        // Right to Left
        if (this.getPageSide(pindex) == 'R') {
            spreadIndices[1] = pindex;
            spreadIndices[0] = pindex + 1;
        } else {
            // Given index was LHS
            spreadIndices[0] = pindex;
            spreadIndices[1] = pindex - 1;
        }
    } else {
        // Left to right
        if (this.getPageSide(pindex) == 'L') {
            spreadIndices[0] = pindex;
            spreadIndices[1] = pindex + 1;
        } else {
            // Given index was RHS
            spreadIndices[1] = pindex;
            spreadIndices[0] = pindex - 1;
        }
    }
    
    return spreadIndices;
}

// For a given "accessible page index" return the page number in the book.
//
// For example, index 5 might correspond to "Page 1" if there is front matter such
// as a title page and table of contents.
br.getPageNum = function(index) {
    return index+1;
}

// Total number of leafs
br.numLeafs = 15;

// Book title and the URL used for the book title link
br.bookTitle= 'Open Library BookReader Presentation';
br.bookUrl  = 'http://openlibrary.org';

// Override the path used to find UI images
br.imagesBaseURL = '../bookreader/images/';

br.getEmbedCode = function(frameWidth, frameHeight, viewParams) {
    return "Embed code not supported in bookreader demo.";
}

// Let's go!
br.init();

// read-aloud and search need backend compenents and are not supported in the demo
$('#BRtoolbar').find('.read').hide();
//$('#textSrch').hide();
$('#btnSrch').hide();
=======
>>>>>>> upstream
