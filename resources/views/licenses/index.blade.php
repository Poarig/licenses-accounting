@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Все лицензии</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLicenseModal">
        Добавить лицензию
    </button>
</div>

<div class="table-responsive">
    <table class="table table-striped" id="licensesTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Продукт</th>
                <th>Максимальное количество</th>
                <th>Номер</th>
                <th>Дата создания</th>
                <th>Организация</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach($licenses as $license)
            <tr>
                <td>{{ $license->id }}</td>
                <td class="editable" 
                    data-id="{{ $license->id }}"
                    data-field="product_id"
                    data-value="{{ $license->product_id }}">
                    {{ $license->product->name }}
                </td>
                <td class="editable" 
                    data-id="{{ $license->id }}"
                    data-field="max_count"
                    data-value="{{ $license->max_count }}">
                    {{ $license->max_count }}
                </td>
                <td class="editable" 
                    data-id="{{ $license->id }}"
                    data-field="number"
                    data-value="{{ $license->number }}">
                    {{ $license->number }}
                </td>
                <td>{{ $license->created_at->format('d.m.Y') }}</td>
                <td>{{ $license->organization->name }}</td>
                <td>
                    <a href="{{ route('pincodes.index', $license) }}" 
                       class="btn btn-sm btn-outline-primary">
                        Пинкоды
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="addLicenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить лицензию</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addLicenseForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="product_id" class="form-label">Продукт *</label>
                            <select class="form-select" id="product_id" name="product_id" required>
                                <option value="">Выберите продукт</option>
                                @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="number" class="form-label">Номер лицензии *</label>
                            <input type="text" class="form-control" id="number" name="number" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="organization_id" class="form-label">Организация *</label>
                        <select class="form-select" id="organization_id" name="organization_id" required>
                            <option value="">Выберите организацию</option>
                            @foreach($organizations as $organization)
                            <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Многопользовательская лицензия -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Многопользовательская лицензия</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="max_count" class="form-label">Максимальное количество пользователей</label>
                                <input type="number" class="form-control" id="max_count" name="max_count" min="1">
                                <div class="form-text">Необязательное поле</div>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="multi_pincodes" class="form-label">Пинкоды (многопользовательские)</label>
                                <textarea class="form-control" id="multi_pincodes" name="multi_pincodes" 
                                          rows="3" placeholder="Введите пинкоды, каждый с новой строки"></textarea>
                                <div class="form-text">Необязательное поле. Каждый пинкод с новой строки</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Однопользовательская лицензия -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Однопользовательская лицензия</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="single_pincodes" class="form-label">Пинкоды (однопользовательские)</label>
                                <textarea class="form-control" id="single_pincodes" name="single_pincodes" 
                                          rows="3" placeholder="Введите пинкоды, каждый с новой строки"></textarea>
                                <div class="form-text">Необязательное поле. Каждый пинкод с новой строки</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="submit-text">Сохранить</span>
                        <div class="spinner-border spinner-border-sm d-none" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Inline редактирование для лицензий
    $('#licensesTable').on('dblclick', '.editable', function() {
        const $cell = $(this);
        const id = $cell.data('id');
        const field = $cell.data('field');
        const value = $cell.data('value');
        
        let $input;
        if (field === 'product_id') {
            // Для продукта используем выпадающий список
            $input = $(`
                <select class="form-select editable-input">
                    <option value="">Выберите продукт</option>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}" ${'{{ $product->id }}' == value ? 'selected' : ''}>
                        {{ $product->name }}
                    </option>
                    @endforeach
                </select>
            `);
        } else {
            const inputType = field === 'max_count' ? 'number' : 'text';
            const minAttr = field === 'max_count' ? 'min="1"' : '';
            $input = $(`<input type="${inputType}" ${minAttr} class="form-control editable-input" value="${value}">`);
        }
        
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
            if (newValue !== value.toString()) {
                // Показываем индикатор загрузки
                $cell.html('<div class="spinner-border spinner-border-sm" role="status"></div>');
                
                $.ajax({
                    url: `licenses/${id}/update-field`,
                    method: 'POST',
                    data: {
                        field: field,
                        value: newValue,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            if (field === 'product_id') {
                                const selectedText = $input.find('option:selected').text();
                                $cell.text(selectedText);
                            } else {
                                $cell.text(newValue);
                            }
                            $cell.data('value', newValue);
                            showNotification('Данные успешно обновлены', 'success');
                        } else {
                            $cell.text($cell.data('value')); // Восстанавливаем старое значение
                            showNotification(response.message || 'Ошибка обновления', 'error');
                        }
                    },
                    error: function(xhr) {
                        $cell.text($cell.data('value')); // Восстанавливаем старое значение
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
    
    // Добавление лицензии
    $('#addLicenseForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $submitText = $submitBtn.find('.submit-text');
        const $spinner = $submitBtn.find('.spinner-border');
        
        // Показываем спиннер
        $submitText.addClass('d-none');
        $spinner.removeClass('d-none');
        $submitBtn.prop('disabled', true);
        
        // Сбрасываем ошибки
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
        
        const formData = {
            product_id: $('#product_id').val(),
            max_count: $('#max_count').val(),
            number: $('#number').val(),
            organization_id: $('#organization_id').val(),
            _token: '{{ csrf_token() }}'
        };
        
        console.log('Отправка данных лицензии:', formData);
        
        $.ajax({
            url: 'licenses',
            method: 'POST',
            data: formData,
            success: function(response) {
                console.log('Ответ сервера:', response);
                
                // Восстанавливаем кнопку
                $submitText.removeClass('d-none');
                $spinner.addClass('d-none');
                $submitBtn.prop('disabled', false);
                
                if (response.success) {
                    $('#addLicenseModal').modal('hide');
                    showNotification(response.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.message || 'Ошибка при добавлении лицензии', 'error');
                }
            },
            error: function(xhr) {
                // Восстанавливаем кнопку
                $submitText.removeClass('d-none');
                $spinner.addClass('d-none');
                $submitBtn.prop('disabled', false);
                
                const response = xhr.responseJSON;
                console.log('Ошибка ответа:', response);
                
                if (response && response.errors) {
                    // Показываем ошибки валидации
                    for (const field in response.errors) {
                        const $field = $(`#${field}`);
                        $field.addClass('is-invalid');
                        $field.siblings('.invalid-feedback').text(response.errors[field][0]);
                    }
                    showNotification('Исправьте ошибки в форме', 'error');
                } else {
                    showNotification(response?.message || 'Ошибка сети или сервера', 'error');
                }
            }
        });
    });
    
    // Сброс формы при закрытии модального окна
    $('#addLicenseModal').on('hidden.bs.modal', function() {
        $('#addLicenseForm')[0].reset();
        $('#addLicenseForm .form-control, #addLicenseForm .form-select').removeClass('is-invalid');
        $('#addLicenseForm .invalid-feedback').text('');
        
        const $submitBtn = $('#addLicenseForm button[type="submit"]');
        $submitBtn.find('.submit-text').removeClass('d-none');
        $submitBtn.find('.spinner-border').addClass('d-none');
        $submitBtn.prop('disabled', false);
    });
    
    // Сброс ошибок при вводе
    $('#addLicenseForm input, #addLicenseForm select').on('input change', function() {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').text('');
    });
});

function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 'alert-info';
    
    const $alert = $(`
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 1060; min-width: 300px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('body').append($alert);
    
    // Автоматическое закрытие через 5 секунд
    setTimeout(() => {
        $alert.alert('close');
    }, 5000);
}
</script>
@endsection