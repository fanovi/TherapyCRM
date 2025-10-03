/**
 * JavaScript per il modulo Statistiche di TherapyCRM
 */

var Statistics = {
    // Flag per evitare doppie inizializzazioni
    initialized: false,
    
    // Configurazione
    config: {
        refreshInterval: 300000, // 5 minuti
        animationDuration: 1000,
        chartColors: {
            primary: '#4e73df',
            success: '#1cc88a',
            info: '#36b9cc',
            warning: '#f6c23e',
            danger: '#e74a3b',
            secondary: '#858796'
        }
    },

    // Cache per i grafici
    charts: {},

    // Inizializzazione
    init: function() {
        // Evita doppie inizializzazioni
        if (this.initialized) {
            // console.log('Statistics module already initialized, skipping...');
            return;
        }
        
        this.initFilters();
        this.initTooltips();
        this.initCounterAnimations();
        this.initAutoRefresh();
        this.initExportButtons();
        
        this.initialized = true;
        // console.log('Statistics module initialized');
    },

    // Inizializza i filtri
    initFilters: function() {
        // Auto-submit del form filtri con debounce
        var filterForm = document.querySelector('form');
        var debounceTimer;
        
        if (filterForm) {
            var inputs = filterForm.querySelectorAll('select, input[type="text"], input[type="number"]');
            inputs.forEach(function(input) {
                input.addEventListener('change', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        filterForm.submit();
                    }, 500);
                });
                input.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        filterForm.submit();
                    }, 500);
                });
            });

            // Submit immediato per checkbox e radio
            var checkboxes = filterForm.querySelectorAll('input[type="checkbox"], input[type="radio"]');
            checkboxes.forEach(function(input) {
                input.addEventListener('change', function() {
                    filterForm.submit();
                });
            });
        }

        // Date range picker handling
        var dateRangePickers = document.querySelectorAll('.date-range-picker');
        dateRangePickers.forEach(function(picker) {
            // Se è presente un date range picker jQuery, intercetta l'evento
            if (picker.daterangepicker) {
                picker.addEventListener('apply.daterangepicker', function(ev) {
                    filterForm.submit();
                });
            }
        });
    },

    // Inizializza tooltip con JavaScript vanilla
    initTooltips: function() {
        var tooltipElements = document.querySelectorAll('[data-toggle="tooltip"], [title]');
        
        tooltipElements.forEach(function(element) {
            // Salva il title originale e rimuovilo per evitare il tooltip del browser
            var tooltipText = element.getAttribute('title') || element.getAttribute('data-original-title') || '';
            if (tooltipText) {
                element.setAttribute('data-tooltip-text', tooltipText);
                element.removeAttribute('title');
                
                // Crea elemento tooltip
                var tooltip = null;
                
                // Mostra tooltip
                element.addEventListener('mouseenter', function(e) {
                    // Crea tooltip element
                    tooltip = document.createElement('div');
                    tooltip.className = 'custom-tooltip';
                    tooltip.textContent = tooltipText;
                    tooltip.style.cssText = `
                        position: absolute;
                        background-color: #333;
                        color: white;
                        padding: 5px 10px;
                        border-radius: 4px;
                        font-size: 12px;
                        z-index: 1000;
                        pointer-events: none;
                        opacity: 0;
                        transition: opacity 0.3s;
                        white-space: nowrap;
                        max-width: 300px;
                    `;
                    
                    document.body.appendChild(tooltip);
                    
                    // Posiziona tooltip
                    var rect = element.getBoundingClientRect();
                    var tooltipRect = tooltip.getBoundingClientRect();
                    
                    // Posizione di default: sopra l'elemento
                    var left = rect.left + (rect.width - tooltipRect.width) / 2;
                    var top = rect.top - tooltipRect.height - 5;
                    
                    // Se non c'è spazio sopra, metti sotto
                    if (top < 0) {
                        top = rect.bottom + 5;
                    }
                    
                    // Assicurati che non esca dai bordi dello schermo
                    if (left < 0) left = 5;
                    if (left + tooltipRect.width > window.innerWidth) {
                        left = window.innerWidth - tooltipRect.width - 5;
                    }
                    
                    tooltip.style.left = left + 'px';
                    tooltip.style.top = top + 'px';
                    
                    // Mostra con animazione
                    setTimeout(function() {
                        if (tooltip) tooltip.style.opacity = '1';
                    }, 10);
                });
                
                // Nascondi tooltip
                element.addEventListener('mouseleave', function() {
                    if (tooltip) {
                        tooltip.style.opacity = '0';
                        setTimeout(function() {
                            if (tooltip && tooltip.parentNode) {
                                tooltip.parentNode.removeChild(tooltip);
                            }
                            tooltip = null;
                        }, 300);
                    }
                });
                
                // Aggiungi cursore help
                element.style.cursor = 'help';
            }
        });
    },

    // Animazioni contatori
    initCounterAnimations: function() {
        var counters = document.querySelectorAll('.counter-animation');
        counters.forEach(function(counter) {
            var finalValue = parseInt(counter.textContent.replace(/[^\d]/g, ''));
            
            if (!isNaN(finalValue)) {
                counter.textContent = '0';
                var startTime = Date.now();
                var duration = Statistics.config.animationDuration;
                
                function animateCounter() {
                    var elapsed = Date.now() - startTime;
                    var progress = Math.min(elapsed / duration, 1);
                    var currentValue = Math.ceil(finalValue * progress);
                    
                    counter.textContent = currentValue.toLocaleString();
                    
                    if (progress < 1) {
                        requestAnimationFrame(animateCounter);
                    }
                }
                
                requestAnimationFrame(animateCounter);
            }
        });
    },

    // Auto-refresh dashboard
    initAutoRefresh: function() {
        if (document.body.classList.contains('dashboard-page')) {
            setInterval(function() {
                Statistics.refreshDashboard();
            }, Statistics.config.refreshInterval);
        }
    },

    // Inizializza pulsanti export
    initExportButtons: function() {
        var exportButtons = document.querySelectorAll('.export-btn');
        exportButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var format = this.dataset.format;
                var type = this.dataset.type;
                Statistics.exportData(type, format);
            });
        });
    },

    // Refresh dashboard
    refreshDashboard: function() {
        var statsCards = document.querySelectorAll('.stats-card');
        statsCards.forEach(function(card) {
            if (card.dataset.ajaxUrl) {
                Statistics.loadCardData(card);
            }
        });

        // Refresh charts
        Object.keys(this.charts).forEach(function(chartId) {
            if (window['reloadChart_' + chartId]) {
                window['reloadChart_' + chartId]();
            }
        });
    },

    // Carica dati card via AJAX
    loadCardData: function(card) {
        var url = card.dataset.ajaxUrl;
        
        this.ajaxRequest(url)
            .then(function(data) {
                if (data.success) {
                    var valueElement = card.querySelector('.card-value');
                    var footerElement = card.querySelector('.card-footer');
                    
                    if (valueElement) {
                        valueElement.textContent = data.value;
                    }
                    if (footerElement) {
                        footerElement.textContent = data.footer || '';
                    }
                } else {
                    // console.warn('Card data response not successful:', data);
                }
            })
            .catch(function(error) {
                // console.error('Errore nel caricamento card data:', error);
                // Mostra errore nell'interfaccia
                var errorElement = card.querySelector('.card-value');
                if (errorElement) {
                    errorElement.textContent = 'Errore';
                    errorElement.style.color = '#e74a3b';
                }
            });
    },

    // Export dati
    exportData: function(type, format) {
        // Raccogli parametri form
        var form = document.querySelector('form');
        var formData = form ? new FormData(form) : new FormData();
        
        // Costruisci URL export
        var exportUrl = '/statistics/export?type=' + type + '&format=' + format;
        
        // Aggiungi parametri form
        var params = new URLSearchParams();
        for (var pair of formData.entries()) {
            params.append(pair[0], pair[1]);
        }
        if (params.toString()) {
            exportUrl += '&' + params.toString();
        }
        
        // Mostra loading
        this.showExportLoading(true);
        
        // Apri in nuova finestra
        var exportWindow = window.open(exportUrl, '_blank');
        
        // Nascondi loading dopo un po'
        setTimeout(function() {
            Statistics.showExportLoading(false);
        }, 2000);
    },

    // Mostra/nascondi loading export
    showExportLoading: function(show) {
        var buttons = document.querySelectorAll('.export-btn');
        buttons.forEach(function(btn) {
            if (show) {
                btn.disabled = true;
                var icon = btn.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-spinner fa-spin';
                }
            } else {
                btn.disabled = false;
                var icon = btn.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-download';
                }
            }
        });
    },

    // Utility per creare grafici
    createChart: function(canvasId, config) {
        var ctx = document.getElementById(canvasId);
        if (!ctx) {
            // console.error('Canvas not found:', canvasId);
            return null;
        }

        // Applica colori di default se non specificati
        if (config.data && config.data.datasets) {
            config.data.datasets.forEach(function(dataset, index) {
                if (!dataset.backgroundColor && !dataset.borderColor) {
                    var colorKey = Object.keys(Statistics.config.chartColors)[index % Object.keys(Statistics.config.chartColors).length];
                    var color = Statistics.config.chartColors[colorKey];
                    
                    if (config.type === 'line') {
                        dataset.borderColor = color;
                        dataset.backgroundColor = color + '20'; // Aggiungi trasparenza
                    } else {
                        dataset.backgroundColor = color;
                    }
                }
            });
        }

        // Se Chart.js è disponibile, usa quello
        if (typeof Chart !== 'undefined') {
            try {
                var chart = new Chart(ctx, config);
                this.charts[canvasId] = chart;
                return chart;
            } catch (error) {
                // console.error('Errore nella creazione del grafico:', error);
                // Mostra messaggio di errore nel canvas
                ctx.style.display = 'none';
                var errorDiv = document.createElement('div');
                errorDiv.className = 'chart-error';
                errorDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>Errore nel caricamento del grafico</div>';
                ctx.parentNode.appendChild(errorDiv);
                return null;
            }
        } else {
            // console.warn('Chart.js non è caricato. Impossibile creare il grafico.');
            // Mostra messaggio di errore nel canvas
            ctx.style.display = 'none';
            var errorDiv = document.createElement('div');
            errorDiv.className = 'chart-error';
            errorDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>Chart.js non disponibile</div>';
            ctx.parentNode.appendChild(errorDiv);
            return null;
        }
    },

    // Utility per aggiornare grafico
    updateChart: function(chartId, newData) {
        var chart = this.charts[chartId];
        if (chart) {
            chart.data = newData;
            chart.update('active');
        }
    },

    // Crea heatmap personalizzata
    createHeatmap: function(containerId, data) {
        var container = document.getElementById(containerId);
        if (!container) {
            // console.error('Container not found:', containerId);
            return;
        }

        // Verifica che i dati siano validi
        if (!data || !data.data || !data.dayLabels) {
            // console.error('Dati heatmap non validi:', data);
            container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>Dati heatmap non disponibili</div>';
            return;
        }

        // Pulisci container
        container.innerHTML = '';

        // Trova valore massimo per normalizzazione
        var maxValue = 0;
        Object.keys(data.data || {}).forEach(function(day) {
            Object.keys(data.data[day] || {}).forEach(function(hour) {
                maxValue = Math.max(maxValue, data.data[day][hour] || 0);
            });
        });

        // Crea tabella
        var table = document.createElement('table');
        table.className = 'table table-sm heatmap-table';

        // Header con ore
        var thead = document.createElement('thead');
        var headerRow = document.createElement('tr');
        headerRow.innerHTML = '<th></th>'; // Cella vuota per giorni
        
        for (var hour = 0; hour < 24; hour++) {
            var th = document.createElement('th');
            th.textContent = hour.toString().padStart(2, '0');
            th.className = 'text-center';
            headerRow.appendChild(th);
        }
        thead.appendChild(headerRow);
        table.appendChild(thead);

        // Body con dati
        var tbody = document.createElement('tbody');
        
        (data.dayLabels || []).forEach(function(dayLabel, dayIndex) {
            var row = document.createElement('tr');
            
            // Cella giorno
            var dayCell = document.createElement('td');
            dayCell.textContent = dayLabel;
            dayCell.className = 'font-weight-bold';
            row.appendChild(dayCell);
            
            // Celle ore
            for (var hour = 0; hour < 24; hour++) {
                var cell = document.createElement('td');
                var value = (data.data[dayIndex] && data.data[dayIndex][hour]) || 0;
                var intensity = maxValue > 0 ? value / maxValue : 0;
                
                cell.textContent = value || '';
                cell.className = 'heatmap-cell text-center ' + Statistics.getHeatmapClass(intensity);
                cell.setAttribute('data-tooltip-text', dayLabel + ' ' + hour.toString().padStart(2, '0') + ':00 - ' + value + ' assenze');
                
                row.appendChild(cell);
            }
            
            tbody.appendChild(row);
        });
        
        table.appendChild(tbody);
        container.appendChild(table);
        
        // Re-inizializza i tooltip per le nuove celle
        this.initTooltips();
    },

    // Ottieni classe CSS per intensità heatmap
    getHeatmapClass: function(intensity) {
        if (intensity === 0) return '';
        if (intensity <= 0.2) return 'heatmap-low';
        if (intensity <= 0.4) return 'heatmap-medium-low';
        if (intensity <= 0.6) return 'heatmap-medium';
        if (intensity <= 0.8) return 'heatmap-medium-high';
        if (intensity <= 0.9) return 'heatmap-high';
        return 'heatmap-very-high';
    },

    // Utility per formattare numeri
    formatNumber: function(num, decimals = 0) {
        return new Intl.NumberFormat('it-IT', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(num);
    },

    // Utility per formattare percentuali
    formatPercentage: function(num, decimals = 1) {
        return new Intl.NumberFormat('it-IT', {
            style: 'percent',
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(num / 100);
    },

    // Mostra/nascondi loading
    showLoading: function(selector, show = true) {
        var element = document.querySelector(selector);
        if (element) {
            if (show) {
                var overlay = document.createElement('div');
                overlay.className = 'loading-overlay';
                overlay.innerHTML = '<div class="spinner-border loading-spinner" role="status"><span class="sr-only">Loading...</span></div>';
                element.appendChild(overlay);
            } else {
                var existingOverlay = element.querySelector('.loading-overlay');
                if (existingOverlay) {
                    existingOverlay.remove();
                }
            }
        }
    },

    // Mostra notifica
    showNotification: function(message, type = 'info') {
        // Crea sistema di notifiche vanilla se toastr non è disponibile
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            // Notifica vanilla
            var notification = document.createElement('div');
            notification.className = 'vanilla-notification notification-' + type;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 4px;
                color: white;
                font-size: 14px;
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s;
                max-width: 300px;
            `;
            
            // Colori per tipo
            var colors = {
                'success': '#1cc88a',
                'error': '#e74a3b',
                'warning': '#f6c23e',
                'info': '#36b9cc'
            };
            
            notification.style.backgroundColor = colors[type] || colors.info;
            
            document.body.appendChild(notification);
            
            // Mostra con animazione
            setTimeout(function() {
                notification.style.opacity = '1';
            }, 10);
            
            // Rimuovi dopo 3 secondi
            setTimeout(function() {
                notification.style.opacity = '0';
                setTimeout(function() {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
    },

    // Utility per chiamate AJAX robuste
    ajaxRequest: function(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            timeout: 10000
        };
        
        const requestOptions = { ...defaultOptions, ...options };
        
        return fetch(url, requestOptions)
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                return response.json();
            })
            .catch(function(error) {
                // console.error('Errore nella richiesta AJAX:', error);
                throw error;
            });
    },

    // Utility per retry automatico
    retryRequest: function(url, options = {}, maxRetries = 3) {
        let attempt = 0;
        
        const attemptRequest = () => {
            attempt++;
            return this.ajaxRequest(url, options)
                .catch(error => {
                    if (attempt < maxRetries) {
                        // console.log(`Tentativo ${attempt} fallito, riprovo...`);
                        return new Promise(resolve => {
                            setTimeout(() => resolve(attemptRequest()), 1000 * attempt);
                        });
                    }
                    throw error;
                });
        };
        
        return attemptRequest();
    },

    // Salva filtri nei localStorage
    saveFilters: function(filters) {
        localStorage.setItem('statistics_filters', JSON.stringify(filters));
    },

    // Carica filtri da localStorage
    loadFilters: function() {
        var saved = localStorage.getItem('statistics_filters');
        return saved ? JSON.parse(saved) : {};
    },

    // Pulisci cache e reset
    reset: function() {
        this.initialized = false;
        this.charts = {};
        // console.log('Statistics module reset');
    }
};

// Inizializzazione quando il documento è pronto
document.addEventListener('DOMContentLoaded', function() {
    Statistics.init();
});

// Esporta oggetto per uso globale
window.Statistics = Statistics;