<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Rumahku Care - Personal Asset Manager')</title>
  
  <!-- Google Font: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  @stack('styles')
</head>
<body>

  @auth
    <!-- Sidebar -->
    @include('layouts.sidebar')

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
      @include('layouts.header')

      <main class="content-body">
        @if (session('success'))
          <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle me-1.5"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @yield('content')
      </main>
    </div>
  @else
    <!-- Guest Layout (Login, Register) -->
    <div class="min-vh-100 d-flex flex-column justify-content-center align-items-center p-3">
      @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="max-width: 480px; width: 100%;">
          <i class="bi bi-check-circle me-1.5"></i> {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      <div class="w-100">
        @yield('content')
      </div>
    </div>
  @endauth

  <!-- Global Modals -->
  @yield('modals')

  <!-- Bootstrap 5 JS Bundle & Model Viewer CDN -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/4.0.0/model-viewer.min.js"></script>
  
  @auth
  <script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarEl = document.getElementById('sidebar');

    sidebarToggle?.addEventListener('click', function(e) {
      e.stopPropagation();
      sidebarEl?.classList.toggle('show');
    });

    document.addEventListener('click', function(e) {
      if (sidebarEl && sidebarEl.classList.contains('show')) {
        if (!sidebarEl.contains(e.target) && !sidebarToggle?.contains(e.target)) {
          sidebarEl.classList.remove('show');
        }
      }
    });
  </script>
  @endauth
  @stack('scripts')
</body>
</html>
