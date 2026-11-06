import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const form = document.querySelector('#transferForm');

if (form) {
    const input = document.querySelector('#fileInput');
    const zone = document.querySelector('#dropZone');
    const browse = document.querySelector('.browse-button');
    const list = document.querySelector('#fileList');
    const button = document.querySelector('#transferButton');
    const chips = document.querySelector('#recipientChips');
    const recipientInput = document.querySelector('#toEmailInput');
    const error = document.querySelector('#formError');
    const scheduleToggle = document.querySelector('#scheduleTransfer');
    const scheduleDateLabel = document.querySelector('.schedule-date');
    const scheduledAt = document.querySelector('#scheduledAt');
    let files = [];
    let recipients = [];

    const localDateTimeValue = (date) => {
        const offset = date.getTimezoneOffset();
        return new Date(date.getTime() - offset * 60000).toISOString().slice(0, 16);
    };

    const minimumSchedule = new Date(Date.now() + 5 * 60000);
    if (scheduleToggle && scheduledAt) {
        scheduledAt.min = localDateTimeValue(minimumSchedule);
        scheduleToggle.addEventListener('change', () => {
            scheduleDateLabel.hidden = !scheduleToggle.checked;
            scheduledAt.required = scheduleToggle.checked;
            if (scheduleToggle.checked && !scheduledAt.value) scheduledAt.value = localDateTimeValue(minimumSchedule);
        });
    }

    const size = (bytes) => bytes < 1048576
        ? `${Math.max(1, Math.round(bytes / 1024))} KB`
        : `${(bytes / 1048576).toFixed(1)} MB`;

    const escapeHtml = (value) => {
        const element = document.createElement('span');
        element.textContent = value;
        return element.innerHTML;
    };

    const render = () => {
        list.innerHTML = files.map((file, index) => `<div class="file-item"><span class="file-icon">FILE</span><span class="file-name">${escapeHtml(file.name)}</span><span class="file-size">${size(file.size)}</span><button type="button" class="remove-file" data-index="${index}" aria-label="Remove file">&times;</button></div>`).join('');
        button.disabled = !files.length || !recipients.length;
    };

    const renderRecipients = () => {
        chips.innerHTML = recipients.map((email, index) => `<span class="recipient-chip">${escapeHtml(email)}<button type="button" data-index="${index}" aria-label="Remove ${escapeHtml(email)}">&times;</button></span>`).join('');
        render();
    };

    const addRecipient = () => {
        const email = recipientInput.value.trim().replace(/,$/, '');
        if (email && !recipients.includes(email)) {
            recipients.push(email);
            recipientInput.value = '';
            renderRecipients();
        }
    };

    recipientInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            addRecipient();
        }
    });
    recipientInput.addEventListener('blur', addRecipient);
    chips.addEventListener('click', (event) => {
        if (event.target.dataset.index !== undefined) {
            recipients.splice(Number(event.target.dataset.index), 1);
            renderRecipients();
        }
    });

    const addFiles = (newFiles) => {
        files = [...files, ...Array.from(newFiles)].filter((file, index, all) => all.findIndex((item) => item.name === file.name && item.size === file.size) === index);
        render();
    };

    browse.addEventListener('click', (event) => {
        event.stopPropagation();
        input.click();
    });
    zone.addEventListener('click', () => input.click());
    zone.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') input.click();
    });
    input.addEventListener('change', (event) => addFiles(event.target.files));
    ['dragenter', 'dragover'].forEach((name) => zone.addEventListener(name, (event) => {
        event.preventDefault();
        zone.classList.add('dragging');
    }));
    ['dragleave', 'drop'].forEach((name) => zone.addEventListener(name, (event) => {
        event.preventDefault();
        zone.classList.remove('dragging');
    }));
    zone.addEventListener('drop', (event) => addFiles(event.dataTransfer.files));
    list.addEventListener('click', (event) => {
        if (event.target.matches('.remove-file')) {
            files.splice(Number(event.target.dataset.index), 1);
            render();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        addRecipient();

        if (!form.reportValidity() || !recipients.length || !files.length) {
            error.textContent = 'Please add at least one recipient and one file.';
            return;
        }

        error.textContent = '';
        button.disabled = true;
        button.innerHTML = 'Sending&hellip;';

        const data = new FormData();
        files.forEach((file) => data.append('files[]', file));
        data.append('from_email', document.querySelector('#fromEmail').value);
        recipients.forEach((email) => data.append('to_emails[]', email));
        data.append('message', document.querySelector('#message').value);
        data.append('expires_in', document.querySelector('#expiresIn').value);
        if (scheduleToggle?.checked && scheduledAt?.value) {
            data.append('scheduled_at', new Date(scheduledAt.value).toISOString());
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: data,
            });
            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                throw new Error(response.redirected && new URL(response.url).pathname === '/login'
                    ? 'Your session has expired. Please log in again.'
                    : 'The server returned an invalid response. Please refresh and try again.');
            }

            const payload = await response.json();

            if (!response.ok) {
                const validationMessage = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                throw new Error(validationMessage || payload.message || 'The transfer could not be sent.');
            }

            await Swal.fire({
                icon: 'success',
                title: scheduleToggle?.checked ? 'Transfer scheduled!' : 'Transfer sent!',
                text: payload.message,
                confirmButtonText: 'View download page',
                confirmButtonColor: '#18251f',
            });
            window.location.href = payload.redirect;
        } catch (exception) {
            await Swal.fire({
                icon: 'error',
                title: 'Transfer failed',
                text: exception.message || 'Something went wrong. Please try again.',
                confirmButtonColor: '#18251f',
            });
            button.disabled = false;
            button.innerHTML = 'Send transfer <span>&rarr;</span>';
        }
    });
}
