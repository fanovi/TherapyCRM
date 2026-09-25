/**
 * Pannello di dettaglio a livelli per le schede della dashboard
 * (es. Pazienti in carico: regime → setting → trattamento).
 *
 * Il server restituisce { total, date, nodes } con nodi
 * { name, count, badge?: {text, variant}, warning?, children?, params? }.
 * Un nodo con params apre l'elenco (listUrl + params) sotto la riga.
 *
 * Uso:
 *   DrilldownPanel.init({
 *     toggle: '#id-scheda', panel: '#id-pannello',
 *     treeUrl: '...', listUrl: '...',
 *     summary: function (data) { return '...'; },
 *     listTitle: function (path, node) { return '...'; },
 *     columns: [{ key: 'name', label: 'Paziente', link: 'url' }, ...]
 *   });
 */
(function (window, document) {
    'use strict';

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (text !== undefined && text !== null) {
            node.textContent = text;
        }
        return node;
    }

    function icon(classes) {
        var i = el('i', classes);
        i.setAttribute('aria-hidden', 'true');
        return i;
    }

    function Panel(options) {
        this.options = options;
        this.toggle = document.querySelector(options.toggle);
        this.panel = document.querySelector(options.panel);
        if (!this.toggle || !this.panel) {
            return;
        }
        this.body = this.panel.querySelector('[data-role="body"]');
        this.summary = this.panel.querySelector('[data-role="summary"]');
        this.hint = this.toggle.querySelector('[data-role="hint"]');
        this.loaded = false;
        this.loading = false;
        this.listCache = {};
        this.bind();
    }

    Panel.prototype.bind = function () {
        var self = this;
        this.toggle.addEventListener('click', function () {
            self.setOpen(self.panel.hidden);
        });
        this.panel.querySelectorAll('[data-action]').forEach(function (button) {
            button.addEventListener('click', function () {
                var action = button.getAttribute('data-action');
                if (action === 'close') {
                    self.setOpen(false);
                    self.toggle.focus();
                } else if (action === 'expand-all') {
                    self.expandAll(true);
                } else if (action === 'collapse-all') {
                    self.expandAll(false);
                }
            });
        });
    };

    Panel.prototype.setOpen = function (open) {
        this.panel.hidden = !open;
        this.toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (this.hint) {
            this.hint.textContent = open ? 'Nascondi dettaglio' : 'Mostra dettaglio';
        }
        if (open && !this.loaded && !this.loading) {
            this.load();
        }
    };

    Panel.prototype.fetchJson = function (url) {
        return fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        });
    };

    Panel.prototype.status = function (container, text, isError) {
        container.innerHTML = '';
        container.appendChild(el('div', 'drilldown-status' + (isError ? ' is-error' : ''), text));
    };

    Panel.prototype.load = function () {
        var self = this;
        this.loading = true;
        this.status(this.body, 'Caricamento…');
        this.fetchJson(this.options.treeUrl).then(function (data) {
            self.loaded = true;
            if (self.summary && self.options.summary) {
                self.summary.textContent = self.options.summary(data);
            }
            self.renderTree(data.nodes || []);
        }).catch(function () {
            self.status(self.body, 'Impossibile caricare i dati. Chiudi e riapri il pannello per riprovare.', true);
        }).then(function () {
            self.loading = false;
        });
    };

    Panel.prototype.renderTree = function (nodes) {
        this.body.innerHTML = '';
        if (!nodes.length) {
            this.status(this.body, 'Nessun dato per oggi.');
            return;
        }
        var self = this;
        nodes.forEach(function (node) {
            self.body.appendChild(self.renderNode(node, 0, []));
        });
    };

    Panel.prototype.renderNode = function (node, level, path) {
        var self = this;
        var wrapper = el('div', 'drilldown-node level-' + level);
        var nodePath = path.concat([node.name]);
        var hasChildren = node.children && node.children.length > 0;
        var isLeaf = !!node.params;

        if (node.warning) {
            var warning = el('div', 'drilldown-row is-warning');
            warning.appendChild(el('span', 'drilldown-icon')).appendChild(icon('fas fa-exclamation-circle'));
            warning.appendChild(el('span', 'drilldown-label', node.name));
            warning.appendChild(el('span', 'drilldown-count', node.count));
            wrapper.appendChild(warning);
            return wrapper;
        }

        var row = el('button', 'drilldown-row' + (isLeaf ? ' is-leaf' : ''));
        row.type = 'button';
        row.setAttribute('aria-expanded', 'false');

        var iconBox = el('span', 'drilldown-icon');
        iconBox.appendChild(icon(isLeaf ? 'fas fa-users' : 'fas fa-chevron-right drilldown-chevron'));
        row.appendChild(iconBox);

        var label = el('span', 'drilldown-label');
        label.appendChild(el('span', 'drilldown-name', node.name));
        if (node.badge && node.badge.text) {
            label.appendChild(el('span', 'drilldown-badge badge-' + node.badge.variant, node.badge.text));
        }
        row.appendChild(label);
        row.appendChild(el('span', 'drilldown-count', node.count));
        wrapper.appendChild(row);

        var content = el('div', isLeaf ? 'drilldown-list' : 'drilldown-children');
        content.hidden = true;
        wrapper.appendChild(content);

        if (isLeaf) {
            row.addEventListener('click', function () {
                var open = content.hidden;
                content.hidden = !open;
                row.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) {
                    self.loadList(content, node, nodePath);
                }
            });
        } else if (hasChildren) {
            node.children.forEach(function (child) {
                content.appendChild(self.renderNode(child, level + 1, nodePath));
            });
            row.addEventListener('click', function () {
                self.setExpanded(row, content, content.hidden);
            });
        } else {
            row.disabled = true;
        }

        return wrapper;
    };

    Panel.prototype.setExpanded = function (row, content, open) {
        content.hidden = !open;
        row.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    Panel.prototype.expandAll = function (open) {
        var self = this;
        this.body.querySelectorAll('.drilldown-children').forEach(function (content) {
            self.setExpanded(content.previousElementSibling, content, open);
        });
        if (!open) {
            this.body.querySelectorAll('.drilldown-list').forEach(function (content) {
                self.setExpanded(content.previousElementSibling, content, false);
            });
        }
    };

    Panel.prototype.loadList = function (container, node, path) {
        var self = this;
        var query = new URLSearchParams(node.params).toString();
        var title = this.options.listTitle ? this.options.listTitle(path, node) : path.join(' › ');
        var render = function (rows) {
            self.renderList(container, title, rows, function () {
                container.hidden = true;
                container.previousElementSibling.setAttribute('aria-expanded', 'false');
                container.previousElementSibling.focus();
            });
        };

        if (this.listCache[query]) {
            render(this.listCache[query]);
            return;
        }
        this.status(container, 'Caricamento…');
        var separator = this.options.listUrl.indexOf('?') === -1 ? '?' : '&';
        this.fetchJson(this.options.listUrl + separator + query).then(function (data) {
            self.listCache[query] = data.rows || [];
            render(self.listCache[query]);
        }).catch(function () {
            self.status(container, 'Impossibile caricare l\'elenco. Riprova.', true);
        });
    };

    Panel.prototype.renderList = function (container, title, rows, onClose) {
        container.innerHTML = '';
        var box = el('div', 'drilldown-list-box');

        var header = el('div', 'drilldown-list-header');
        header.appendChild(el('span', 'drilldown-list-title', title));
        var close = el('button', 'drilldown-list-close', 'Chiudi elenco');
        close.type = 'button';
        close.addEventListener('click', onClose);
        header.appendChild(close);
        box.appendChild(header);

        var scroll = el('div', 'drilldown-list-scroll');
        if (!rows.length) {
            scroll.appendChild(el('div', 'drilldown-status', 'Nessun paziente.'));
        } else {
            var table = el('table', 'drilldown-table');
            var headRow = table.appendChild(el('thead')).appendChild(el('tr'));
            this.options.columns.forEach(function (column) {
                headRow.appendChild(el('th', column.align === 'right' ? 'text-right' : '', column.label));
            });
            var tbody = table.appendChild(el('tbody'));
            var columns = this.options.columns;
            rows.forEach(function (row) {
                var tr = tbody.appendChild(el('tr'));
                columns.forEach(function (column) {
                    var td = tr.appendChild(el('td', column.align === 'right' ? 'text-right' : ''));
                    var link = column.link ? row[column.link] : null;
                    if (link) {
                        var a = el('a', null, row[column.key]);
                        a.href = link;
                        td.appendChild(a);
                    } else {
                        td.textContent = row[column.key];
                    }
                });
            });
            scroll.appendChild(table);
        }
        box.appendChild(scroll);
        container.appendChild(box);
    };

    window.DrilldownPanel = {
        init: function (options) {
            return new Panel(options);
        }
    };
})(window, document);
