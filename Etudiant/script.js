// Timer functionality
function startTimer(duration) {
    let timer = duration * 60;
    const countdown = document.getElementById('countdown');
    
    const timerInterval = setInterval(function() {
        const minutes = parseInt(timer / 60, 10);
        const seconds = parseInt(timer % 60, 10);

        countdown.textContent = minutes + ":" + (seconds < 10 ? "0" : "") + seconds;

        if (--timer < 0) {
            clearInterval(timerInterval);
            document.getElementById('examForm').submit();
        }
    }, 1000);
}

// Save progress functionality
function saveProgress() {
    const formData = new FormData(document.getElementById('examForm'));
    formData.append('save_progress', true);

    fetch('takeExam.php?examId=' + formData.get('exam_id'), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Progrès sauvegardé avec succès!');
        } else {
            alert('Erreur lors de la sauvegarde: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur est survenue lors de la sauvegarde');
    });
}

// Form validation
document.getElementById('examForm').addEventListener('submit', function(e) {
    if (!confirm('Êtes-vous sûr de vouloir soumettre l\'examen? Cette action est définitive.')) {
        e.preventDefault();
    }
});
