@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Организации</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOrganizationModal">
        Добавить организацию
    </button>
</div>

<div class="table-responsive">
    <table class="table table-striped" id="organizationsTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach($organizations as $organization)
            <tr>
                <td>{{ $organization->id }}</td>
                <td class="editable" 
                    data-id="{{ $organization->id }}"
                    data-field="name"
                    data-value="{{ $organization->name }}">
                    {{ $organization->name }}
                </td>
                <td>
                    <a href="{{ route('licenses.organization', $organization) }}" 
                       class="btn btn-sm btn-outline-primary">
                        Лицензии
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="addOrganizationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить организацию</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addOrganizationForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Название организации</label>
                        <input type="text" class="form-control" id="name" name="name" required>
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
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Inline редактирование
    $('.editable').on('dblclick', function() {
        const $cell = $(this);
        const id = $cell.data('id');
        const field = $cell.data('field');
        const value = $cell.data('value');
        
        const $input = $(`<input type="text" class="form-control editable-input" value="${value}">`);
        $input.css({
            top: $cell.offset().top,
            left: $cell.offset().left,
            width: $cell.outerWidth(),
            height: $cell.outerHeight()
        });
        
        $('body').append($input);
        $input.focus();
        
        function saveValue() {
            const newValue = $input.val().trim();
            if (newValue !== value) {
                showLoading($cell);
                
                $.ajax({
                    url: `/organizations/${id}/update-field`,
                    method: 'POST',
                    data: {
                        field: field,
                        value: newValue,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        hideLoading($cell);
                        if (response.success) {
                            $cell.text(newValue).data('value', newValue);
                            showNotification('Данные успешно обновлены', 'success');
                        } else {
                            showNotification(response.message || 'Ошибка обновления', 'error');
                        }
                    },
                    error: function(xhr) {
                        hideLoading($cell);
                        const response = xhr.responseJSON;
                        if (response && response.errors) {
                            showNotification(Object.values(response.errors)[0][0], 'error');
                        } else {
                            showNotification(response?.message || 'Ошибка обновления', 'error');
                        }
                    }
                });
            }
            $input.remove();
        }
        
        function cancelEdit() {
            $input.remove();
        }
        
        $input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                saveValue();
            } else if (e.key === 'Escape') {
                cancelEdit();
            }
        });
        
        $input.on('blur', function() {
            saveValue();
        });
    });
    
    // Добавление организации
    $('#addOrganizationForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('Добавление...');
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
        
        const formData = {
            name: $('#name').val().trim(),
            _token: '{{ csrf_token() }}'
        };
        
        console.log('Отправка данных:', formData); // Для отладки
        
        $.ajax({
            url: '/organizations',
            method: 'POST',
            data: formData,
            success: function(response) {
                $submitBtn.prop('disabled', false).text(originalText);
                console.log('Ответ сервера:', response); // Для отладки
                
                if (response.success) {
                    $('#addOrganizationModal').modal('hide');
                    showNotification(response.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(response.message || 'Ошибка при добавлении', 'error');
                }
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).text(originalText);
                const response = xhr.responseJSON;
                console.log('Ошибка ответа:', response); // Для отладки
                
                if (response && response.errors) {
                    // Показываем ошибки валидации
                    for (const field in response.errors) {
                        $(`#${field}`).addClass('is-invalid');
                        $(`#${field}`).siblings('.invalid-feedback').text(response.errors[field][0]);
                    }
                } else {
                    showNotification(response?.message || 'Ошибка сети или сервера', 'error');
                }
            }
        });
    });
    
    $('#addOrganizationModal').on('hidden.bs.modal', function() {
        $('#addOrganizationForm')[0].reset();
        $('#addOrganizationForm .form-control').removeClass('is-invalid');
        $('#addOrganizationForm .invalid-feedback').text('');
        $('#addOrganizationForm button[type="submit"]').prop('disabled', false).text('Сохранить');
    });
    
    // Сброс ошибок при вводе
    $('#name').on('input', function() {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').text('');
    });
});

function showNotification(message, type = 'info') {
    // Временное решение - можно заменить на toast
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const $alert = $(`
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 1060;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    $('body').append($alert);
    
    setTimeout(() => {
        $alert.alert('close');
    }, 5000);
}

function showLoading($element) {
    $element.html('<div class="spinner-border spinner-border-sm" role="status"></div>');
}

function hideLoading($element) {
    // Восстановление текста произойдет в success/error обработчиках
}
</script>
@endsection