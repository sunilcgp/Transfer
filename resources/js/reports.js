import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const reportTable = document.querySelector('#transferReportsTable');

const textElement = (tagName, text, className = '') => {
    const element = document.createElement(tagName);
    element.textContent = text;
    element.className = className;

    return element;
};

const stackedText = (primary, secondary) => {
    const wrapper = document.createElement('span');
    wrapper.className = 'report-stacked-text';
    wrapper.append(textElement('strong', primary), textElement('small', secondary));

    return wrapper;
};

const statusPill = (status) => {
    const labels = { active: 'Active', downloaded: 'Downloaded', expired: 'Expired' };
    const pill = textElement('span', labels[status], `status-pill ${status}-pill`);
    pill.prepend(document.createElement('i'));

    return pill;
};

const activitySummary = (activity) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'report-activity';
    const items = [
        ['WhatsApp', activity.whatsapp, 'whatsapp'],
        ['Copied', activity.copies, 'copy'],
        ['Opens', activity.opens, 'open'],
        ['Downloads', activity.downloads, 'download'],
        ['Devices', activity.devices, 'device'],
    ];

    items.forEach(([label, count, type]) => {
        const item = textElement('span', `${label} ${count}`, `activity-chip activity-${type}`);
        wrapper.append(item);
    });

    return wrapper;
};

const resendButton = (row) => {
    const button = textElement('button', row.can_resend ? 'Send again' : 'Unavailable', 'report-resend-button');
    button.type = 'button';
    button.disabled = !row.can_resend;
    button.dataset.resendUrl = row.resend_url;

    return button;
};

if (reportTable) {
    new DataTable(reportTable, {
        processing: true,
        serverSide: true,
        ajax: {
            url: reportTable.dataset.source,
            headers: {
                Accept: 'application/json',
            },
            error: (_request, _status, error) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Reports unavailable',
                    text: error === 'parsererror'
                        ? 'Your session may have expired. Please refresh and log in again.'
                        : 'The reports could not be loaded. Please refresh and try again.',
                    confirmButtonColor: '#18251f',
                });
            },
        },
        order: [[0, 'desc']],
        pageLength: 15,
        autoWidth: false,
        scrollX: true,
        scrollCollapse: true,
        columnDefs: [
            { targets: 0, width: '58px' },
            { targets: [1, 2], width: '180px' },
            { targets: [3, 4], width: '210px' },
            { targets: 5, width: '100px' },
            { targets: [6, 7, 8, 9], width: '125px' },
            { targets: 10, width: '90px' },
            { targets: 11, width: '205px' },
            { targets: 12, width: '110px' },
        ],
        layout: {
            topStart: {
                pageLength: {
                    menu: [10, 15, 25, 50],
                },
            },
            topEnd: {
                search: {
                    placeholder: 'Search reports',
                },
            },
            bottomStart: 'info',
            bottomEnd: {
                paging: {
                    buttons: 5,
                    boundaryNumbers: false,
                    firstLast: false,
                    previousNext: true,
                },
            },
        },
        language: {
            emptyTable: 'No report records yet',
            info: 'Showing _START_ to _END_ of _TOTAL_ records',
            infoEmpty: 'No transfer records',
            lengthMenu: 'Show _MENU_ records',
            processing: 'Loading reports…',
            search: 'Search:',
            zeroRecords: 'No matching transfer reports',
        },
        columns: [
            {
                data: 'id',
                render: (data, type) => type === 'display' ? textElement('strong', `#${data}`) : data,
            },
            {
                data: 'sender_email',
                render: (data, type) => type === 'display' ? textElement('span', data) : data,
            },
            {
                data: 'recipient_emails',
                orderable: false,
                render: (data, type) => {
                    if (type !== 'display') {
                        return data.join(', ');
                    }

                    const recipients = document.createElement('div');
                    recipients.className = 'report-recipients';
                    data.forEach((email) => recipients.append(textElement('span', email)));

                    return recipients;
                },
            },
            {
                data: 'message',
                render: (data, type) => type === 'display' ? textElement('span', data, 'report-message') : data,
            },
            {
                data: 'file_names',
                orderable: false,
                render: (data, type) => {
                    if (type !== 'display') {
                        return data.join(', ');
                    }

                    const fileNames = document.createElement('div');
                    fileNames.className = 'report-file-names';
                    data.forEach((name) => fileNames.append(textElement('span', name)));

                    return fileNames;
                },
            },
            {
                data: 'files',
                orderable: false,
                searchable: false,
                render: (data, type) => {
                    if (type !== 'display') {
                        return data.map((file) => file.name).join(', ');
                    }

                    const actions = document.createElement('div');
                    actions.className = 'report-file-actions';

                    data.forEach((file) => {
                        const link = textElement('a', 'View file', 'report-view-file');
                        link.href = file.view_url;
                        link.target = '_blank';
                        link.rel = 'noopener';
                        link.setAttribute('aria-label', `View ${file.name}`);
                        actions.append(link);
                    });

                    return actions;
                },
            },
            {
                data: 'file_count',
                render: (data, type, row) => type === 'display'
                    ? stackedText(`${data} ${data === 1 ? 'file' : 'files'}`, row.total_size)
                    : data,
            },
            {
                data: 'sent_date',
                render: (data, type, row) => type === 'display' ? stackedText(data, row.sent_time) : data,
            },
            {
                data: 'expiry_date',
                render: (data, type, row) => type === 'display' ? stackedText(data, row.expiry_time) : data,
            },
            {
                data: 'download_date',
                render: (data, type, row) => {
                    if (type !== 'display') {
                        return data;
                    }

                    return data
                        ? stackedText(data, row.download_time)
                        : textElement('span', 'Not downloaded', 'report-muted');
                },
            },
            {
                data: 'status',
                orderable: false,
                render: (data, type) => type === 'display' ? statusPill(data) : data,
            },
            {
                data: 'activity',
                orderable: false,
                searchable: false,
                render: (data, type) => type === 'display'
                    ? activitySummary(data)
                    : data.whatsapp + data.copies + data.opens + data.downloads,
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: (data, type, row) => type === 'display' ? resendButton(row) : '',
            },
        ],
    });

    reportTable.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-resend-url]');
        if (!button || button.disabled) return;

        const confirmation = await Swal.fire({
            icon: 'question',
            title: 'Send this transfer again?',
            text: 'The email will be sent again to all original recipients.',
            showCancelButton: true,
            confirmButtonText: 'Yes, send again',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#18251f',
        });
        if (!confirmation.isConfirmed) return;

        button.disabled = true;
        button.textContent = 'Sending…';

        try {
            const response = await fetch(button.dataset.resendUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                throw new Error(response.redirected && new URL(response.url).pathname === '/login'
                    ? 'Your session has expired. Please log in again.'
                    : 'The server returned an invalid response. Please refresh and try again.');
            }

            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Email could not be sent.');

            await Swal.fire({ icon: 'success', title: 'Email sent!', text: payload.message, confirmButtonColor: '#18251f' });
            button.textContent = 'Send again';
            button.disabled = false;
        } catch (exception) {
            await Swal.fire({ icon: 'error', title: 'Send failed', text: exception.message, confirmButtonColor: '#18251f' });
            button.textContent = 'Send again';
            button.disabled = false;
        }
    });
}
