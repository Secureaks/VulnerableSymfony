const message = new URLSearchParams(window.location.search).get('message');
const div = document.getElementById('float-message');

if (message && div) {
    p = document.createElement('p');
    p.innerText = message;
    div.append(p);
    div.style.display = 'block';
    setTimeout(() => {
        div.style.display = 'none';
    }, 5000);
}