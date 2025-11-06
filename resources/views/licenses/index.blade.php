@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Все лицензии</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#licenseModal">
        Добавить лицензию
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Номер лицензии</th>
                        <th>Организация</th>
                        <th>Продукт</th>
                        <th>Макс. количество</th>
                        <th>Файл лицензии</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($licenses as $license)
                    <tr>
                        <td>{{ $license->id }}</td>
                        <td>
                            <span class="editable" data-field="number" data-id="{{ $license->id }}">
                                {{ $license->number }}
                            </span>
                        </td>
                        <td>{{ $license->organization->name }}</td>
                        <td>
                            <span class="editable" data-field="product_id" data-id="{{ $license->id }}" data-value="{{ $license->product_id }}">
                                {{ $license->product->name }}
                            </span>
                        </td>
                        <td>
                            <span class="editable" data-field="max_count" data-id="{{ $license->id }}">
                                {{ $license->max_count ?? '—' }}
                            </span>
                        </td>
                        <td>
                            @if($license->hasFile())
                                <div class="file-info">
                                    <a href="{{ route('licenses.download-file', $license) }}" 
                                       class="btn btn-sm btn-outline-success download-file-link"
                                       title="Скачать {{ $license->archive_name }}"
                                       onclick="trackDownload({{ $license->id }})">
                                        <i class="bi bi-download"></i> {{ $license->archive_name }}
                                    </a>
                                    <small class="text-muted d-block">{{ $license->getFileSizeFormatted() }}</small>
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('pincodes.index', $license) }}" class="btn btn-sm btn-outline-primary">
                                Пинкоды
                            </a>
                        </td>
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
                            <label for="organization_id" class="form-label">Организация *</label>
                            <select class="form-control" id="organization_id" name="organization_id" required>
                                <option value="">Выберите организацию</option>
                                @foreach($organizations as $organization)
                                <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="number" class="form-label">Номер лицензии *</label>
                            <input type="text" class="form-control" id="number" name="number" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="max_count" class="form-label">Максимальное количество</label>
                            <input type="number" class="form-control" id="max_count" name="max_count" min="1">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="license_file" class="form-label">Файл лицензии (архив)</label>
                        <input required type="file" class="form-control" id="license_file" name="license_file" 
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

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Обработка формы добавления лицензии
    $('#licenseForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
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
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    for (const field in errors) {
                        $(`#${field}`).addClass('is-invalid');
                        $(`#${field}`).siblings('.invalid-feedback').text(errors[field][0]);
                    }
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

    function trackDownload(licenseId) {
        // Можно добавить аналитику или логирование скачиваний
        console.log('Downloading file for license:', licenseId);
        
        // Опционально: отправка события на сервер
        $.ajax({
            url: '/licenses/' + licenseId + '/track-download',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('Download tracked');
            },
            error: function(xhr) {
                console.log('Download tracking failed');
            }
        });
    }

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
        
        setTimeout(() => {
            $alert.alert('close');
        }, 5000);
    }
});
</script>

<style>
.download-file-link {
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.875rem;
}
.download-file-link:hover {
    text-decoration: none;
}
</style>
@endsection