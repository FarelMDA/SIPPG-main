<div class="grid grid-cols-1 gap-6 md:grid-cols-3">
    <div class="card p-4 text-center">
        <p class="text-sm text-ink-muted">% Kehadiran Bulan Ini</p>
        <p class="mt-1 text-4xl font-bold text-brand-primary">{{ $data['persentase_bulan_ini'] ?? 0 }}%</p>
    </div>
    <div
        class="md:col-span-2"
        x-data="{
            init() {
                new window.Chart(this.$refs.canvas, {
                    type: 'line',
                    data: {
                        labels: @js(collect($data['tren_6_bulan'] ?? [])->pluck('periode')),
                        datasets: [{
                            label: '% Kehadiran',
                            data: @js(collect($data['tren_6_bulan'] ?? [])->pluck('persentase')),
                            borderColor: '#076B3B',
                            backgroundColor: 'rgba(7,107,59,0.15)',
                            tension: 0.3,
                            fill: true,
                        }],
                    },
                    options: { scales: { y: { min: 0, max: 100 } }, plugins: { legend: { display: false } } },
                });
            }
        }"
    >
        <p class="mb-2 text-sm text-ink-muted">Tren 6 Bulan</p>
        <canvas x-ref="canvas"></canvas>
    </div>
</div>
