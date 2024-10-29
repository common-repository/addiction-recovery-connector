jQuery(function () { jQuery("img.lazy").lazyload({ effect: "fadeIn" }); });

const trueCallback = function (observer) {
    const freshImages = document.querySelectorAll('.fresh');
    if (freshImages.length > 0) {
        freshImages.forEach(function (item) {
            item.classList.remove('fresh');
        });
    }
    jQuery.ajax({
        url: '/wp-admin/admin-ajax.php',
        type: 'post',
        data: {
            action: 'ar_get_page',
            page: document.querySelector('#ar-page').value
        },
        success: function (items) {
            if (items.indexOf('<a') > -1) {
                document.querySelector('.ar-connect-feed-wrapper').insertAdjacentHTML('beforeend', items);
                document.querySelector('#ar-page').value = Number(document.querySelector('#ar-page').value) + 1;
                jQuery(function () { jQuery('img.fresh').lazyload({ effect: 'fadeIn' }); });
                observer.observe(document.querySelector('.ar-connect-feed-wrapper > div:last-child'));
            }
        }
    })
}

window.onload = () => {
    const options = {
        root: null,
        rootMargin: '0px',
        threshold: 0.2
    }
    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                observer.unobserve(document.querySelector('.ar-connect-feed-wrapper > div:last-child'));
                trueCallback(observer);
            }
        })
    }, options)
    const arr = document.querySelector('.ar-connect-feed-wrapper > div:last-child');
    observer.observe(arr)
}
