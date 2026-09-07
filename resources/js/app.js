// De grafiekbibliotheek is relatief groot. Laad hem pas op de Overview waar
// hij daadwerkelijk nodig is, zodat de andere dashboardpagina's lichter
// openen.
window.loadApexCharts = () => import('apexcharts').then(({ default: ApexCharts }) => ApexCharts);
