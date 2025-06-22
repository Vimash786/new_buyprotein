"use strict";

// Dashboard initialization function
function initializeDashboard() {
    // Initialize ECharts if they exist
    if (typeof echarts !== 'undefined') {
        // Line Chart
        var lineChart = document.getElementById('echart_graph_line');
        if (lineChart) {
            var lineChartInstance = echarts.init(lineChart);
            // Re-apply the chart configuration from index.js
            if (window.lineChartConfig) {
                lineChartInstance.setOption(window.lineChartConfig);
            }
        }

        // Area Chart
        var areaChart = document.getElementById('echart_area_line');
        if (areaChart) {
            var areaChartInstance = echarts.init(areaChart);
            if (window.areaChartConfig) {
                areaChartInstance.setOption(window.areaChartConfig);
            }
        }

        // Bar Chart
        var barChart = document.getElementById('echart_bar');
        if (barChart) {
            var barChartInstance = echarts.init(barChart);
            if (window.barChartConfig) {
                barChartInstance.setOption(window.barChartConfig);
            }
        }
    }

    // Initialize Chart.js if it exists
    if (typeof Chart !== 'undefined') {
        var canvas = document.getElementById('chart-1');
        if (canvas) {
            // Re-initialize Chart.js charts
            if (window.chartConfig) {
                new Chart(canvas, window.chartConfig);
            }
        }
    }

    // Initialize tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Initialize other dashboard components
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

// Run on initial load
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

// Re-run when Livewire navigates
document.addEventListener('livewire:navigated', function() {
    initializeDashboard();
});

// Alternative for older Livewire versions
document.addEventListener('turbo:load', function() {
    initializeDashboard();
});
