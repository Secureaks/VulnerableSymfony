const message = new URLSearchParams(window.location.search).get('message');
const div = document.getElementById('float-message');

if (message && div) {
    div.innerHTML = "<p>" + message + "</p>";
    div.style.display = 'block';
    setTimeout(() => {
        div.style.display = 'none';
    }, 5000);
}