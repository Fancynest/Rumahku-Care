<div class="d-flex align-items-center gap-2 mb-4 border-bottom border-slate-200 pb-2">
  <ul class="nav nav-pills gap-2" role="tablist">
    <li class="nav-item">
      <a href="{{ route('schedules.index') }}" 
         class="btn btn-sm rounded-pill px-3.5 py-2 text-decoration-none d-flex align-items-center gap-1.5 {{ request()->routeIs('schedules.index') ? 'btn-dark' : 'btn-glass text-secondary' }}" 
         style="min-height: 44px; font-weight: 500;">
        <i class="bi bi-list-task"></i>
        <span>Mendatang</span>
      </a>
    </li>
    <li class="nav-item">
      <a href="{{ route('schedules.calendar') }}" 
         class="btn btn-sm rounded-pill px-3.5 py-2 text-decoration-none d-flex align-items-center gap-1.5 {{ request()->routeIs('schedules.calendar') ? 'btn-dark' : 'btn-glass text-secondary' }}" 
         style="min-height: 44px; font-weight: 500;">
        <i class="bi bi-calendar3"></i>
        <span>Kalender</span>
      </a>
    </li>
    <li class="nav-item">
      <a href="{{ route('schedules.history') }}" 
         class="btn btn-sm rounded-pill px-3.5 py-2 text-decoration-none d-flex align-items-center gap-1.5 {{ request()->routeIs('schedules.history') ? 'btn-dark' : 'btn-glass text-secondary' }}" 
         style="min-height: 44px; font-weight: 500;">
        <i class="bi bi-clock-history"></i>
        <span>Riwayat</span>
      </a>
    </li>
  </ul>
</div>
