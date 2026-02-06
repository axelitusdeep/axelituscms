document.addEventListener('submit', function(e) {
    const emailInput = e.target.querySelector('input[name="email"]');
    
    if (emailInput) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button');

        const params = new URLSearchParams();
        params.append('email', emailInput.value);

        btn.disabled = true;
        btn.innerText = 'PROCESSING...';

        fetch(form.action, {
            method: 'POST',
            body: params,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if(data.success) emailInput.value = '';
        })
        .catch(err => {
            console.error(err);
            alert('Error, server unreachable.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerText = 'Subscribe';
        });
    }
});