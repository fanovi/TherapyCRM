/**
 * JavaScript per il modulo Statistiche di TherapyCRM
 */

var Statistics = {
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
        this.initFilters();
        this.initTooltips();
        this.initCounterAnimations();
        this.initAutoRefresh();
        this.initExportButtons();
        
        console.log('Statistics module initialized');
    },

    // Inizializza i filtri
    initFilters: function() {
        // Auto-submit del form filtri con debounce
        var filterForm = $('form');
        var debounceTimer;
        
        filterForm.find('select, input[type="text"], input[type="number"]').on('change input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                filterForm.submit();
            }, 500);
        });

        // Submit immediato per checkbox e radio
        filterForm.find('input[type="checkbox"], input[type="radio"]').on('change', function() {
            filterForm.submit();
        });

        // Date range picker handling
        $('.date-range-picker').on('apply.daterangepicker', function(ev, picker) {
            filterForm.submit();
        });
    },

    // Inizializza tooltip
    initTooltips: function() {
        $('[data-toggle="tooltip"]').tooltip({
            html: true,
            delay: { show: 500, hide: 100 }
        });
    },

    // Animazioni contatori
    initCounterAnimations: function() {
        $('.counter-animation').each(function() {
            var $this = $(this);
            var finalValue = parseInt($this.text().replace(/[^\d]/g, ''));
            
            if (!isNaN(finalValue)) {
                $this.text('0');
                $({ counter: 0 }).animate({ counter: finalValue }, {
                    duration: Statistics.config.animationDuration,
                    easing: 'swing',
                    step: function() {
                        $this.text(Math.ceil(this.counter).toLocaleString());
                    }
                });
            }
        });
    },

    // Auto-refresh dashboard
    initAutoRefresh: function() {
        if ($('body').hasClass('dashboard-page')) {
            setInterval(function() {
                Statistics.refreshDashboard();
            }, this.config.refreshInterval);
        }
    },

    // Inizializza pulsanti export
    initExportButtons: function() {
        $('.export-btn').on('click', function(e) {
            e.preventDefault();
            var format = $(this).data('format');
            var type = $(this).data('type');
            Statistics.exportData(type, format);
        });
    },

    // Refresh dashboard
    refreshDashboard: function() {
        $('.stats-card').each(function() {
            var $card = $(this);
            if ($card.data('ajax-url')) {
                Statistics.loadCardData($card);
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
    loadCardData: function($card) {
        var url = $card.data('ajax-url');
        
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $card.find('.card-value').text(response.value);
                    $card.find('.card-footer').text(response.footer || '');
                }
            },
            error: function(xhr, status, error) {
                console.error('Errore nel caricamento card data:', error);
            }
        });
    },

    // Export dati
    exportData: function(type, format) {
        // Raccogli parametri form
        var formData = $('form').serialize();
        
        // Costruisci URL export
        var exportUrl = '/statistics/export?type=' + type + '&format=' + format;
        if (formData) {
            exportUrl += '&' + formData;
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
        var $btn = $('.export-btn');
        if (show) {
            $btn.prop('disabled', true);
            $btn.find('i').removeClass().addClass('fas fa-spinner fa-spin');
        } else {
            $btn.prop('disabled', false);
            $btn.find('i').removeClass().addClass('fas fa-download');
        }
    },

    // Utility per creare grafici
    createChart: function(canvasId, config) {
        var ctx = document.getElementById(canvasId);
        if (!ctx) {
            console.error('Canvas not found:', canvasId);
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

        var chart = new Chart(ctx, config);
        this.charts[canvasId] = chart;
        return chart;
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
            console.error('Container not found:', containerId);
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
                cell.title = dayLabel + ' ' + hour.toString().padStart(2, '0') + ':00 - ' + value + ' assenze';
                
                row.appendChild(cell);
            }
            
            tbody.appendChild(row);
        });
        
        table.appendChild(tbody);
        container.appendChild(table);

        // Inizializza tooltip per le celle
        $(container).find('.heatmap-cell').tooltip();
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
        var $element = $(selector);
        if (show) {
            $element.append('<div class="loading-overlay"><div class="spinner-border loading-spinner" role="status"><span class="sr-only">Loading...</span></div></div>');
        } else {
            $element.find('.loading-overlay').remove();
        }
    },

    // Mostra notifica
    showNotification: function(message, type = 'info') {
        // Usa toastr se disponibile, altrimenti alert
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            alert(message);
        }
    },

    // Salva filtri nei localStorage
    saveFilters: function(filters) {
        localStorage.setItem('statistics_filters', JSON.stringify(filters));
    },

    // Carica filtri da localStorage
    loadFilters: function() {
        var saved = localStorage.getItem('statistics_filters');
        return saved ? JSON.parse(saved) : {};
    }
};

// Inizializzazione quando il documento è pronto
$(document).ready(function() {
    Statistics.init();
});

// Esporta oggetto per uso globale
window.Statistics = Statistics; 