@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Профиль пользователя</h4>
            </div>
            <div class="card-body">
                <form id="profileForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Имя</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $user->name }}" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="surname" class="form-label">Фамилия</label>
                            <input type="text" class="form-control" id="surname" name="surname" value="{{ $user->surname }}" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="patronymic" class="form-label">Отчество</label>
                        <input type="text" class="form-control" id="patronymic" name="patronymic" value="{{ $user->patronymic }}">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="login" class="form-label">Логин</label>
                        <input type="text" class="form-control" id="login" name="login" value="{{ $user->login }}" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Новый пароль</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <div class="form-text">Оставьте пустым, если не хотите менять пароль.</div>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Подтверждение пароля</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        <div class="invalid-feedback"></div>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#profileForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            name: $('#name').val(),
            surname: $('#surname').val(),
            patronymic: $('#patronymic').val(),
            login: $('#login').val(),
            password: $('#password').val(),
            password_confirmation: $('#password_confirmation').val(),
            _token: '{{ csrf_token() }}'
        };
        
        $.ajax({
            url: '{{ route("profile.update") }}',
            method: 'PUT',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotification('Профиль успешно обновлен', 'success');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                for (const field in errors) {
                    $(`#${field}`).addClass('is-invalid');
                    $(`#${field}`).siblings('.invalid-feedback').text(errors[field][0]);
                }
            }
        });
    });
    
    // Сброс ошибок при вводе
    $('#profileForm input').on('input', function() {
        $(this).removeClass('is-invalid');
    });
});
</script>
@endsection