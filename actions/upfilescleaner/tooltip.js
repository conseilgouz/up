function showThumbnail(imagePath, event) {
    var thumb = document.getElementById('thumbnail-preview');
    thumb.innerHTML = '<img src="' + imagePath + '" style="max-width:180px;max-height:180px;" />';
    thumb.style.display = 'block';
    // Positionnement à côté du curseur
    var x = event.clientX + 20;
    var y = event.clientY + 20;
    thumb.style.left = x + 'px';
    thumb.style.top = y + 'px';
}
function hideThumbnail() {
    var thumb = document.getElementById('thumbnail-preview');
    thumb.style.display = 'none';
    thumb.innerHTML = '';
}
// Gestion du déplacement de la vignette avec la souris
document.addEventListener('mousemove', function(e) {
    var thumb = document.getElementById('thumbnail-preview');
    if (thumb.style.display === 'block') {
        thumb.style.left = (e.clientX + 20) + 'px';
        thumb.style.top = (e.clientY + 20) + 'px';
    }
});