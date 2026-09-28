window.addEventListener('scroll', function (e) {
    var anchorTop = document.getElementById('scroll');
    if (window.scrollY > 300) {
        anchorTop.classList.remove('disabled');
    } else {
        anchorTop.classList.add('disabled');
    }
});
