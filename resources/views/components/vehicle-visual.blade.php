@props(['vehicle'])

@php
    $model = $vehicle->vehicleModel;
    $glb = $model?->asset_glb ? asset($model->asset_glb) : null;
    $image = $model?->asset_image ? asset($model->asset_image) : null;
    $bodyType = $vehicle->body_type ?? 'default';

    // Generic fallback path check
    $genericRelative = "images/vehicles/generic/{$bodyType}.svg";
    $hasGeneric = file_exists(public_path($genericRelative));
    $genericAsset = $hasGeneric ? asset($genericRelative) : asset('images/vehicles/generic/default.svg');
@endphp

<div class="vehicle-visual-container position-relative d-flex flex-column align-items-center justify-content-center w-100" style="min-height: 250px;">
    @if ($glb)
        {{-- Layer A: True 3D with <model-viewer> --}}
        <model-viewer
            id="modelViewer-{{ $vehicle->id }}"
            src="{{ $glb }}"
            alt="{{ $vehicle->name }}"
            auto-rotate
            rotation-per-second="15deg"
            camera-controls
            shadow-intensity="1.5"
            shadow-softness="1"
            loading="lazy"
            style="width: 100%; height: 260px; background-color: transparent;"
        >
        </model-viewer>

        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const viewer = document.getElementById('modelViewer-{{ $vehicle->id }}');
                if (viewer) {
                    viewer.addEventListener('load', function() {
                        const material = viewer.model.materials.find(m => m.name.toLowerCase().includes('body') || m.name.toLowerCase().includes('paint')) || viewer.model.materials[0];
                        if (material && window.selectedColorFactor) {
                            material.pbrMetallicRoughness.setBaseColorFactor(window.selectedColorFactor);
                        }
                    });
                }
            });
        </script>
        @endpush
    @elseif ($image)
        {{-- Layer B: Transparent Studio Image --}}
        <div class="vehicle-image-wrapper position-relative text-center w-100 py-2">
            <img 
                src="{{ $image }}" 
                alt="{{ $vehicle->name }}"
                class="img-fluid"
                style="max-height: 230px; object-fit: contain; filter: drop-shadow(0 16px 20px rgba(15, 23, 42, 0.22));"
            >
            <div class="vehicle-ground-shadow"></div>
        </div>
    @else
        {{-- Layer C & D: Generic Silhouette / Neutral Placeholder --}}
        <div class="vehicle-image-wrapper position-relative text-center w-100 py-3">
            <img 
                src="{{ $genericAsset }}" 
                alt="{{ $vehicle->name }}"
                class="img-fluid"
                style="max-height: 180px; object-fit: contain; opacity: 0.85;"
            >
            <small class="text-muted d-block mt-2" style="font-size: 0.72rem;">Ilustrasi Kategori: {{ strtoupper(str_replace('_', ' ', $bodyType)) }}</small>
        </div>
    @endif
</div>
