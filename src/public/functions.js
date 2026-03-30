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

            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    e.target.textContent = 'copied to clipboard'; // TODO: refactor this part
                    setTimeout(() => {
                        e.target.textContent = text;
                        clickedOnCell = false;
                    }, 1000);
                })
            } else {
                fallbackCopyTextToClipboard(text, () => {
                    e.target.textContent = 'copied to clipboard'; // TODO: duplicate
                    setTimeout(() => {
                        e.target.textContent = text;
                        clickedOnCell = false;
                    }, 1000);
                });
            }
        }
    });
});


function fallbackCopyTextToClipboard(text, callback) {
    // 1. Створюємо тимчасовий елемент textarea
    const textArea = document.createElement("textarea");
    textArea.value = text;

    // 2. Робимо його невидимим, але залишаємо в DOM (щоб можна було виділити)
    textArea.style.position = "fixed";
    textArea.style.left = "-9999px";
    textArea.style.top = "0";
    document.body.appendChild(textArea);

    // 3. Виділяємо текст всередині
    textArea.focus();
    textArea.select();

    try {
        // 4. Виконуємо команду копіювання
        const successful = document.execCommand('copy');
        const msg = successful ? 'успішно' : 'не вдалося';
        console.log('Копіювання через execCommand: ' + msg);
    } catch (err) {
        console.error('Помилка копіювання:', err);
    }

    // 5. Видаляємо тимчасовий елемент
    document.body.removeChild(textArea);
    callback();
}