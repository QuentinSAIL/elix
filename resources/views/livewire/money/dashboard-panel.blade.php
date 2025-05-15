<div wire:click="edit">
    <h2 class="text-2xl font-bold mb-8">{{ $title }}</h2>
    <canvas wire:ignore id="{{ $panel->id }}"></canvas>
</div>

<script>
    document.addEventListener('livewire:navigated', function () {
        const panelId = @json($panel->id);
        const labels = @json($labels);
        const data = @json($values);
        const colors = @json($colors);
        const type = @json($panel->type);

        const ctx = document.getElementById(panelId).getContext('2d');
        new Chart(ctx, {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    labels: labels,
                    data: data,
                    backgroundColor: [
                        ...colors,
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderWidth: 0
                }]
            },
        });
    });
</script>
