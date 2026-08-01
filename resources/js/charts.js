import {
    CategoryScale,
    Chart,
    Filler,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';

// Register only what the dashboard draws — Chart.js is tree-shakeable and
// pulling in the whole bundle would add ~100KB of unused controllers.
Chart.register(
    CategoryScale,
    Filler,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
);

/**
 * Palette validated with the dataviz skill's checker against this app's own
 * surfaces (#ffffff light, #171717 dark): lightness band, chroma floor and
 * 3:1 contrast all pass in both modes.
 */
export const chartTheme = {
    light: {
        series: '#2a78d6',
        fill: 'rgba(42, 120, 214, 0.10)',
        surface: '#ffffff',
        grid: '#e1e0d9',
        axis: '#c3c2b7',
        muted: '#898781',
        tooltipBg: '#0b0b0b',
        tooltipText: '#ffffff',
    },
    dark: {
        series: '#3987e5',
        fill: 'rgba(57, 135, 229, 0.14)',
        surface: '#171717',
        grid: '#2c2c2a',
        axis: '#383835',
        muted: '#898781',
        tooltipBg: '#fafafa',
        tooltipText: '#0b0b0b',
    },
};

export function currentTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches
        ? chartTheme.dark
        : chartTheme.light;
}

export { Chart };
