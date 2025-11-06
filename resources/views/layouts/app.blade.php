<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Учет лицензий 1С</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .editable:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .editable-input {
            position: absolute;
            z-index: 1000;
            background: white;
            border: 1px solid #0d6efd;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.8) !important;
        }
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: white !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('organizations.index') }}">1С License Manager</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    @auth
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('organizations.index') ? 'active' : '' }}" 
                           href="{{ route('organizations.index') }}">Организации</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('licenses.index') ? 'active' : '' }}" 
                           href="{{ route('licenses.index') }}">Все лицензии</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('actions.index') ? 'active' : '' }}" 
                           href="{{ route('actions.index') }}">История</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}" 
                           href="{{ route('products.index') }}">Продукты</a>
                    </li>
                    @if(auth()->user()->isAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}" 
                           href="{{ route('users.index') }}">Пользователи</a>
                    </li>
                    @endif
                    @endauth
                </ul>
                <ul class="navbar-nav">
                    @auth
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('profile.index') ? 'active' : '' }}" 
                           href="{{ route('profile.index') }}">
                            Профиль ({{ auth()->user()->name }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link nav-link" style="border: none; background: none;">
                                Выход
                            </button>
                        </form>
                    </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    {{-- Модальное окно для уведомлений --}}
    <div class="modal fade" id="notificationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Уведомление</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="notificationMessage"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    
    
    // Глобальная функция для показа уведомлений
    function showNotification(message, type = 'info') {
        const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
        const messageElement = document.getElementById('notificationMessage');
        
        // Устанавливаем стили в зависимости от типа
        messageElement.className = '';
        if (type === 'error') {
            messageElement.classList.add('text-danger', 'fw-bold');
        } else if (type === 'success') {
            messageElement.classList.add('text-success', 'fw-bold');
        }
        
        messageElement.textContent = message;
        notificationModal.show();
    }

    // Глобальный обработчик ошибок AJAX
    $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
        if (jqXHR.status === 401) {
            showNotification('Сессия истекла. Пожалуйста, войдите снова.', 'error');
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
        } else if (jqXHR.status === 500) {
            showNotification('Внутренняя ошибка сервера. Пожалуйста, попробуйте позже.', 'error');
        } else if (jqXHR.status === 403) {
            showNotification('Доступ запрещен. Недостаточно прав.', 'error');
        }
    });
    </script>

    @yield('scripts')
</body>
</html>