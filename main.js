// This line makes sure our JavaScript code runs only after the full HTML page has loaded.
document.addEventListener("DOMContentLoaded", function() {
    // We call our functions to load the header and footer into the page.
    loadHeader();
    loadFooter();
});

// This function loads the header.html file.
function loadHeader() {
    // Create a request object to get a file from the server.
    var xhr = new XMLHttpRequest();

    // This function will run when we get a response from the server.
    xhr.onreadystatechange = function() {
        // We check if the request is finished (readyState 4).
        if (this.readyState === 4) {
            // Then we check if it was successful (status 200).
            if (this.status === 200) {
                // Find the div with the id "header-placeholder" and put the header HTML inside it.
                document.getElementById("header-placeholder").innerHTML = this.responseText;
                // Now that the header is loaded, we can highlight the active navigation link.
                highlightActiveLink();
            } else {
                console.error("Error loading header.html. Status: " + this.status);
            }
        }
    };

    // Tell the request which file to get ("header.html").
    xhr.open("GET", "header.html", true);
    // Send the request.
    xhr.send();
}

// This function loads the footer.html file. It works just like the loadHeader function.
function loadFooter() {
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                document.getElementById("footer-placeholder").innerHTML = this.responseText;
            } else {
                console.error("Error loading footer.html. Status: " + this.status);
            }
        }
    };
    xhr.open("GET", "footer.html", true);
    xhr.send();
}

// This function adds a special "active" style to the link for the current page.
function highlightActiveLink() {
    // Get all the navigation links from the header.
    var navLinks = document.querySelectorAll('.nav-links a');
    // Get the file name of the current page (e.g., "Community.html").
    var currentPage = window.location.pathname.split('/').pop();

    // If currentPage is empty, it means we are on the homepage (index.html).
    if (currentPage === "") {
        currentPage = "index.html";
    }

    // Go through each link one by one.
    for (var i = 0; i < navLinks.length; i++) {
        // If a link's destination (href) matches the current page's name...
        if (navLinks[i].getAttribute('href') === currentPage) {
            // ...add the "active" class to it to make it stand out.
            navLinks[i].classList.add('active');
        }
    }
}