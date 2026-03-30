// Вибір алгоритму
document.querySelectorAll('.algo-card:not(.disabled)').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.algo-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        card.querySelector('input[type=radio]').checked = true;
    });
});

// Перемикання вкладок
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('d-none'));
    document.querySelectorAll('#authTabs .nav-link').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.remove('d-none');
    btn.classList.add('active');
}

// Автоматичне копіювання хешу до буферу обміну з таблиці дашборду
document.querySelectorAll('.hash-cell').forEach(hashCell => {
    let clickedOnCell = false;
    hashCell.addEventListener('click', (e) => {
        if (!clickedOnCell) {
            clickedOnCell = true;
            const text = e.target.textContent.trim();
            navigator.clipboard.writeText(text).then(() => {
                e.target.textContent = 'copied to clipboard';
                setTimeout(() => {
                    e.target.textContent = text;
                    clickedOnCell = false;
                }, 1000);
            })
        }
    });
});