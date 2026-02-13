(function () {
    const isDomainsPage = /\/CMD_DOMAIN/.test(window.location.pathname) || /\/user\/domains\.html/.test(window.location.pathname);
    if (!isDomainsPage) {
        return;
    }

    const createButton = (domain, actionCell) => {
        if (!domain || !actionCell || actionCell.querySelector('.hosts-click-btn')) {
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'hosts-click-btn';
        button.textContent = 'Temporary URL';
        button.style.marginLeft = '8px';
        button.style.padding = '4px 8px';
        button.style.border = '1px solid #2563eb';
        button.style.background = '#2563eb';
        button.style.color = '#fff';
        button.style.borderRadius = '4px';
        button.style.cursor = 'pointer';
        button.style.fontSize = '12px';

        button.addEventListener('click', async () => {
            button.disabled = true;
            const original = button.textContent;
            button.textContent = 'Working...';

            try {
                const response = await fetch(`/CMD_PLUGINS/hosts-click/create.php?domain=${encodeURIComponent(domain)}`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (response.ok && data.preview_url) {
                    try {
                        await navigator.clipboard.writeText(data.preview_url);
                    } catch (error) {
                        // ignore clipboard failures
                    }
                    window.open(data.preview_url, '_blank', 'noopener');
                } else {
                    alert(data.error || 'Failed to create preview link.');
                }
            } catch (error) {
                alert('Failed to create preview link.');
            } finally {
                button.disabled = false;
                button.textContent = original;
            }
        });

        actionCell.appendChild(button);
    };

    const findDomain = (row) => {
        const dataDomain = row.getAttribute('data-domain');
        if (dataDomain) {
            return dataDomain.trim();
        }

        const firstCell = row.querySelector('td');
        if (!firstCell) {
            return null;
        }

        const text = firstCell.textContent || '';
        const match = text.match(/[A-Za-z0-9-]+\.[A-Za-z]{2,}/);
        return match ? match[0] : null;
    };

    const injectButtons = () => {
        const rows = document.querySelectorAll('table tbody tr');
        rows.forEach((row) => {
            const domain = findDomain(row);
            if (!domain) {
                return;
            }
            const cells = row.querySelectorAll('td');
            if (!cells.length) {
                return;
            }
            const actionCell = cells[cells.length - 1];
            createButton(domain, actionCell);
        });
    };

    injectButtons();

    const observer = new MutationObserver(() => {
        injectButtons();
    });

    observer.observe(document.body, { childList: true, subtree: true });
})();
