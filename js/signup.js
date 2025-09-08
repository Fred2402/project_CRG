document.addEventListener('DOMContentLoaded', function () {
    var expSelect = document.getElementById('experience_volontaire');
    var statusDiv = document.getElementById('status_volontaire_div');

    expSelect.addEventListener('change', function () {
        // Affiche le div si l'utilisateur sélectionne "Oui"
        if (expSelect.value === 'yes') {
            statusDiv.style.display = 'block';
        } else {
            statusDiv.style.display = 'none';
        }
    });
});
