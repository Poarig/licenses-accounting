@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Управление пользователями</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="setModalToAdd()">
        Добавить пользователя
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Логин</th>
                        <th>Роль</th>
                        <th>Статус</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr class="{{ $user->trashed() ? 'table-danger' : '' }}">
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->surname }} {{ $user->name }} {{ $user->patronymic }}</td>
                        <td>{{ $user->login }}</td>
                        <td>
                            @if($user->isAdmin())
                                <span class="badge bg-danger">Администратор</span>
                            @else
                                <span class="badge bg-secondary">Пользователь</span>
                            @endif
                        </td>
                        <td>
                            @if($user->trashed())
                                <span class="badge bg-danger">Удален</span>
                            @else
                                <span class="badge bg-success">Активен</span>
                            @endif
                        </td>
                        <td>{{ $user->created_at->timezone('Europe/Moscow')->format('d.m.Y H:i') }}</td>
                        <td>
                            @if($user->trashed())
                                <button class="btn btn-sm btn-success restore-user" data-id="{{ $user->id }}">
                                    Восстановить
                                </button>
                            @else
                                <button class="btn btn-sm btn-primary edit-user" 
                                        data-user-id="{{ $user->id }}"
                                        data-user-name="{{ $user->name }}"
                                        data-user-surname="{{ $user->surname }}"
                                        data-user-patronymic="{{ $user->patronymic }}"
                                        data-user-login="{{ $user->login }}"
                                        data-user-role="{{ $user->role }}">
                                    Изменить
                                </button>
                                @if($user->id !== auth()->id())
                                    <button class="btn btn-sm btn-danger delete-user" 
                                            data-id="{{ $user->id }}" 
                                            data-name="{{ $user->surname }} {{ $user->name }}">
                                        Удалить
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-outline-secondary" disabled>
                                        Нельзя удалить
                                    </button>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления/редактирования пользователя -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">Добавить пользователя</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm">
                <div class="modal-body">
                    <input type="hidden" id="user_id" name="id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Имя *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="surname" class="form-label">Фамилия *</label>
                            <input type="text" class="form-control" id="surname" name="surname" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="patronymic" class="form-label">Отчество</label>
                        <input type="text" class="form-control" id="patronymic" name="patronymic">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="login" class="form-label">Логин *</label>
                        <input type="text" class="form-control" id="login" name="login" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Пароль <span id="passwordRequired">*</span></label>
                        <input type="password" class="form-control" id="password" name="password">
                        <div class="form-text" id="passwordHelp">Минимум 8 символов</div>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Подтверждение пароля <span id="passwordConfirmationRequired">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Роль *</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="user">Пользователь</option>
                            <option value="admin">Администратор</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения удаления -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить пользователя <strong id="deleteUserName"></strong>?</p>
                <p class="text-muted">Пользователь будет помечен как удаленный и не сможет войти в систему.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Удалить</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let currentUserId = null;

    // Функция для установки модального окна в режим добавления
    window.setModalToAdd = function() {
        $('#userModalTitle').text('Добавить пользователя');
        $('#userForm')[0].reset();
        $('#user_id').val('');
        $('#password').attr('required', true);
        $('#password_confirmation').attr('required', true);
        $('#passwordRequired').show();
        $('#passwordConfirmationRequired').show();
        $('#passwordHelp').text('Минимум 8 символов');
        clearValidationErrors();
        
        // Сбрасываем выпадающий список к значению по умолчанию
        $('#role').val('user');
    };

    // Функция для установки модального окна в режим редактирования
    function setModalToEdit(userId, userName, userSurname, userPatronymic, userLogin, userRole) {
        $('#userModalTitle').text('Редактировать пользователя');
        $('#user_id').val(userId);
        $('#name').val(userName);
        $('#surname').val(userSurname);
        $('#patronymic').val(userPatronymic || '');
        $('#login').val(userLogin);
        $('#role').val(userRole);
        $('#password').removeAttr('required');
        $('#password_confirmation').removeAttr('required');
        $('#passwordRequired').hide();
        $('#passwordConfirmationRequired').hide();
        $('#passwordHelp').text('Оставьте пустым, если не хотите менять пароль');
        clearValidationErrors();
        
        // Показываем модальное окно
        $('#userModal').modal('show');
    }

    // Обработчик для кнопки редактирования
    $(document).on('click', '.edit-user', function() {
        const userId = $(this).data('user-id');
        const userName = $(this).data('user-name');
        const userSurname = $(this).data('user-surname');
        const userPatronymic = $(this).data('user-patronymic');
        const userLogin = $(this).data('user-login');
        const userRole = $(this).data('user-role');
        
        console.log('Editing user:', { userId, userName, userSurname, userPatronymic, userLogin, userRole });
        
        setModalToEdit(userId, userName, userSurname, userPatronymic, userLogin, userRole);
    });

    // Обработка формы
    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const userId = $('#user_id').val();
        const url = userId ? `/users/${userId}` : '/users';
        const method = userId ? 'PUT' : 'POST';

        console.log('Submitting form:', { userId, url, method });

        // Если это редактирование и пароль не заполнен, удаляем поля пароля из FormData
        if (userId && !$('#password').val()) {
            formData.delete('password');
            formData.delete('password_confirmation');
        }

        // Для PUT запросов добавляем параметр _method
        if (method === 'PUT') {
            formData.append('_method', 'PUT');
        }

        // Логируем данные формы для отладки
        const formDataObj = {};
        for (let [key, value] of formData.entries()) {
            formDataObj[key] = value;
        }
        console.log('Form data:', formDataObj);

        $.ajax({
            url: url,
            method: 'POST', // Всегда используем POST, для PUT добавляем _method
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Success response:', response);
                if (response.success) {
                    $('#userModal').modal('hide');
                    showNotification(response.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error response:', xhr.responseJSON);
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    clearValidationErrors();
                    for (const field in errors) {
                        $(`#${field}`).addClass('is-invalid');
                        $(`#${field}`).siblings('.invalid-feedback').text(errors[field][0]);
                    }
                    // Показываем первую ошибку в уведомлении
                    const firstError = Object.values(errors)[0][0];
                    showNotification(firstError, 'error');
                } else {
                    const errorMessage = xhr.responseJSON?.message || 'Произошла ошибка при сохранении';
                    showNotification(errorMessage, 'error');
                }
            }
        });
    });



    // Подтверждение удаления
    $(document).on('click', '.delete-user', function() {
        currentUserId = $(this).data('id');
        const userName = $(this).data('name');
        $('#deleteUserName').text(userName);
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').click(function() {
        if (currentUserId) {
            $.ajax({
                url: `/users/${currentUserId}`,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        $('#deleteModal').modal('hide');
                        showNotification(response.message, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON?.message || 'Произошла ошибка при удалении';
                    showNotification(error, 'error');
                }
            });
        }
    });

    // Восстановление пользователя
    $(document).on('click', '.restore-user', function() {
        const userId = $(this).data('id');
        
        if (confirm('Вы уверены, что хотите восстановить этого пользователя?')) {
            $.ajax({
                url: `/users/${userId}/restore`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(response.message, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                },
                error: function(xhr) {
                    showNotification('Произошла ошибка при восстановлении', 'error');
                }
            });
        }
    });

    // Сброс валидации при закрытии модального окна
    $('#userModal').on('hidden.bs.modal', function () {
        clearValidationErrors();
    });

    function clearValidationErrors() {
        $('#userForm input, #userForm select').removeClass('is-invalid');
        $('#userForm .invalid-feedback').text('');
    }
});
</script>
@endsection