@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Лицензии организации: {{ $organization->name }}</h1>
    <div>
        @if(auth()->user()->isAdmin())
            @if(isset($showDeleted) && $showDeleted)
                <a href="{{ route('licenses.organization', $organization) }}" class="btn btn-secondary me-2">
                    Не удалённые записи
                </a>
            @else
                <a href="{{ route('licenses.organization.deleted', $organization) }}" class="btn btn-warning me-2">
                    Удалённые записи
                </a>
            @endif
        @endif
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#licenseModal">
            Добавить лицензию
        </button>
        <a href="{{ route('licenses.index') }}" class="btn btn-secondary">
            Все лицензии
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Номер лицензии</th>
                        <th>Продукт</th>
                        <th>Макс. количество</th>
                        <th>Файл лицензии</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($licenses as $license)
                    <tr class="{{ isset($showDeleted) && $showDeleted ? 'table-danger' : '' }}">
                        <td>{{ $license->id }}</td>
                        <td>
                            @if(!isset($showDeleted) || !$showDeleted)
                                <span class="editable" data-field="number" data-id="{{ $license->id }}">
                                    {{ $license->number }}
                                </span>
                            @else
                                {{ $license->number }}
                            @endif
                        </td>
                        <td>
                            @if(!isset($showDeleted) || !$showDeleted)
                                <span class="editable" data-field="product_id" data-id="{{ $license->id }}" data-value="{{ $license->product_id }}">
                                    {{ $license->product->name }}
                                </span>
                            @else
                                {{ $license->product->name }}
                            @endif
                        </td>
                        <td>
                            @if(!isset($showDeleted) || !$showDeleted)
                                <span class="editable" data-field="max_count" data-id="{{ $license->id }}">
                                    {{ $license->max_count ?? '—' }}
                                </span>
                            @else
                                {{ $license->max_count ?? '—' }}
                            @endif
                        </td>
                        <td>
                            @if($license->hasFile())
                                <a href="{{ route('licenses.download-file', $license) }}" 
                                   class="btn btn-sm btn-outline-success download-file-link"
                                   title="Скачать {{ $license->file_name }} ({{ $license->getFileSizeFormatted() }})">
                                    <i class="bi bi-download"></i> {{ $license->file_name }}
                                    <small class="text-muted">({{ $license->getFileSizeFormatted() }})</small>
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if(isset($showDeleted) && $showDeleted)
                                <span>{{ $license->deleted_at->timezone('Europe/Moscow')->format('d.m.Y H:i') }}</span>
                            @else
                                <a href="{{ route('pincodes.index', $license) }}" class="btn btn-sm btn-outline-primary">
                                    Пинкоды
                                </a>
                                @if(auth()->user()->isAdmin())
                                    <button class="btn btn-sm btn-danger delete-license" 
                                            data-id="{{ $license->id }}" 
                                            data-name="{{ $license->number }}">
                                        Удалить
                                    </button>
                                @endif
                            @endif
                        </td>
                        @if(isset($showDeleted) && $showDeleted)
                            <td>
                                <button class="btn btn-sm btn-success restore-license" data-id="{{ $license->id }}">
                                    Восстановить
                                </button>
                            </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления лицензии -->
<div class="modal fade" id="licenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить лицензию</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="licenseForm" enctype="multipart/form-data">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="organization_id" value="{{ $organization->id }}">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="product_id" class="form-label">Продукт *</label>
                            <select class="form-control" id="product_id" name="product_id" required>
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
                        <label for="max_count" class="form-label">Максимальное количество</label>
                        <input type="number" class="form-control" id="max_count" name="max_count" min="1">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="license_file" class="form-label">Файл лицензии (архив)</label>
                        <input type="file" class="form-control" id="license_file" name="license_file" 
                               accept=".zip,.rar,.7z,.tar,.gz">
                        <div class="form-text">Допустимые форматы: ZIP, RAR, 7Z, TAR, GZ. Максимальный размер: 10MB</div>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="single_pincodes" class="form-label">Однопользовательские пинкоды</label>
                        <textarea class="form-control" id="single_pincodes" name="single_pincodes" 
                                  rows="4" placeholder="Введите пинкоды, каждый с новой строки"></textarea>
                        <div class="form-text">По одному пинкоду на строку</div>
                    </div>
                    <div class="mb-3">
                        <label for="multi_pincodes" class="form-label">Многопользовательские пинкоды</label>
                        <textarea class="form-control" id="multi_pincodes" name="multi_pincodes" 
                                  rows="4" placeholder="Введите пинкоды, каждый с новой строки"></textarea>
                        <div class="form-text">По одному пинкоду на строку</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Добавить лицензию</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения удаления -->
<div class="modal fade" id="deleteLicenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить лицензию <strong id="deleteLicenseName"></strong>?</p>
                <p class="text-muted">Лицензия будет помечена как удаленная и не будет отображаться в основном списке.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmLicenseDelete">Удалить</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Обработка формы добавления лицензии
    $('#licenseForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Показываем индикатор загрузки
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Добавление...');
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: '/licenses',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#licenseModal').modal('hide');
                    $('#licenseForm')[0].reset();
                    showNotification(response.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            },
            error: function(xhr) {
                // Восстанавливаем кнопку
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
                
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    for (const field in errors) {
                        $(`#${field}`).addClass('is-invalid');
                        $(`#${field}`).siblings('.invalid-feedback').text(errors[field][0]);
                    }
                } else {
                    showNotification('Произошла ошибка при добавлении лицензии', 'error');
                }
            }
        });
    });

    // Inline-редактирование
    $(document).on('click', '.editable', function() {
        const field = $(this).data('field');
        const id = $(this).data('id');
        const currentValue = $(this).data('value') || $(this).text().trim();
        
        let $input;
        if (field === 'product_id') {
            // Для продукта показываем выпадающий список
            $input = $(`<select class="form-control editable-input">
                @foreach($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>`).val(currentValue);
        } else {
            $input = $('<input type="' + (field === 'max_count' ? 'number' : 'text') + '" class="form-control editable-input">')
                .val(currentValue);
        }
        
        $input.css({
            top: $(this).offset().top,
            left: $(this).offset().left,
            width: $(this).outerWidth(),
            height: $(this).outerHeight()
        });
        
        $('body').append($input);
        $input.focus();
        
        $input.on('blur keypress', function(e) {
            if (e.type === 'blur' || (e.type === 'keypress' && e.which === 13)) {
                const newValue = $input.val().trim();
                if (newValue !== currentValue && newValue !== '') {
                    $.ajax({
                        url: `/licenses/${id}/update-field`,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            field: field,
                            value: newValue
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
                            const errors = xhr.responseJSON?.errors;
                            if (errors) {
                                let errorMessage = 'Ошибка обновления:\n';
                                for (const field in errors) {
                                    errorMessage += errors[field][0] + '\n';
                                }
                                showNotification(errorMessage, 'error');
                            }
                        }
                    });
                }
                $input.remove();
            }
        });
    });

    // Сброс валидации при закрытии модального окна
    $('#licenseModal').on('hidden.bs.modal', function () {
        $('#licenseForm input, #licenseForm select').removeClass('is-invalid');
        $('#licenseForm .invalid-feedback').text('');
    });

    // Сброс ошибок при вводе в модальном окне
    $('#licenseForm input, #licenseForm select, #licenseForm textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').text('');
    });

    function showNotification(message, type = 'info') {
        // Удаляем предыдущие уведомления
        $('.alert-dismissible.position-fixed').remove();
        
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
        
        setTimeout(() => {
            $alert.alert('close');
        }, 5000);
    }

    let currentLicenseId = null;

    // Подтверждение удаления лицензии
    $(document).on('click', '.delete-license', function() {
        currentLicenseId = $(this).data('id');
        const licenseName = $(this).data('name');
        $('#deleteLicenseName').text(licenseName);
        $('#deleteLicenseModal').modal('show');
    });

    $('#confirmLicenseDelete').click(function() {
        if (currentLicenseId) {
            $.ajax({
                url: `/licenses/${currentLicenseId}`,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        $('#deleteLicenseModal').modal('hide');
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

    // Восстановление лицензии
    $(document).on('click', '.restore-license', function() {
        const licenseId = $(this).data('id');
        
        if (confirm('Вы уверены, что хотите восстановить эту лицензию?')) {
            $.ajax({
                url: `/licenses/${licenseId}/restore`,
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
});
</script>

<style>
.download-file-link {
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.875rem;
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.download-file-link:hover {
    text-decoration: none;
}
.editable:hover {
    background-color: #f8f9fa;
    cursor: pointer;
    border-radius: 3px;
    padding: 2px 4px;
}
.editable-input {
    position: absolute;
    z-index: 1000;
    background: white;
    border: 1px solid #0d6efd;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 4px;
}
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
.table th {
    font-weight: 600;
    background-color: #f8f9fa;
}
</style>
@endsection