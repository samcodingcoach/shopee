function updateCredentials() {
    const select = document.getElementById('app_select');
    const selectedOption = select.options[select.selectedIndex];

    if (select.value) {
        document.getElementById('partner_id').value = select.value;
    } else {
        document.getElementById('partner_id').value = '';
    }
}

function copyToken(id) {
    const input = document.getElementById(id);
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value);

    const btn = input.nextElementSibling;
    const originalText = btn.textContent;
    btn.textContent = '✓ Copied';
    btn.classList.add('copied');

    setTimeout(() => {
        btn.textContent = originalText;
        btn.classList.remove('copied');
    }, 2000);
}

function copyValue(value) {
    navigator.clipboard.writeText(value);
}
