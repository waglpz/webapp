function isIterable(obj) {
    if (obj == null) {
        return false;
    }
    return typeof obj[Symbol.iterator] === 'function';
}

async function postData(url = '', data = {}) {
    return await fetch(url, {
        method: 'POST',
        mode: 'same-origin',
        cache: 'no-cache',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json; charset=UTF-8'
        },
        body: JSON.stringify(isIterable(data) ? Object.fromEntries(data) : data)
    });
}

async function getData(url = '') {
    return await fetch(url, {
        method: 'GET',
        mode: 'same-origin',
        cache: 'no-cache',
        headers: {
            'Accept': 'text/html',
            'Content-Type': 'text/html; charset=UTF-8'
        },
    });
}

function render400(response) {
    response.then(data => console.log('Response data', data))
    return response
}

function httpError(response) {
    return new Error(
        `Network response was not ok, Response status ${response.status}, status text ${response.statusText}`
    );
}


(function () {
    'use strict';
    window.addEventListener('load', function () {
        // const navLinks = document.getElementsByClassName('nav-link');
        // for (let navLink of navLinks) {
        //     $(navLink).parent().removeClass('active');
        //     if (navLink.href === window.location.href) {
        //         $(navLink).parent().addClass('active');
        //     }
        // }

        const scrollToTopButton = document.getElementById('js-top');
        const scrollFunc = () => {
            let y = window.scrollY;
            if (y > 0) {
                scrollToTopButton.className = "top-link show";
            } else {
                scrollToTopButton.className = "top-link hide";
            }
        };

        window.addEventListener("scroll", scrollFunc);
        const scrollToTop = () => {
            const c = document.documentElement.scrollTop || document.body.scrollTop;
            if (c > 0) {
                window.requestAnimationFrame(scrollToTop);
                window.scrollTo(0, c - c / 10);
            }
        };

        scrollToTopButton.onclick = (e) => {
            e.preventDefault();
            scrollToTop();
        }
    })
})();
