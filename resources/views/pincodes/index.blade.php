@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Пинкоды лицензии: {{ $license->number }}</h1>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPincodesModal">
            Добавить пинкоды
        </button>
        <a href="{{ route('licenses.organization', $license->organization) }}" class="btn btn-secondary">
            Назад к лицензиям
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
                        <th>Пинкод</th>
                        <th>Тип</th>
                        <th>Статус</th>
                        <th>Устройство</th>
                        <th>Файл активации</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pincodes as $pincode)
                    <tr>
                        <td>{{ $pincode->id }}</td>
                        <td><code>{{ $pincode->value }}</code></td>
                        <td>
                            @if($pincode->type == 'single')
                                <span class="badge bg-info">Однопользовательский</span>
                            @else
                                <span class="badge bg-warning">Многопользовательский</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge 
                                @if($pincode->status == 'active') bg-success
                                @elseif($pincode->status == 'used') bg-danger
                                @else bg-secondary
                                @endif">
                                @if($pincode->status == 'nonactivated') Не активирован
                                @elseif($pincode->status == 'active') Активирован
                                @elseif($pincode->status == 'used') Использован
                                @endif
                            </span>
                        </td>
                        <td>{{ $pincode->device_information ?? '—' }}</td>
                        <td>
                            @php
                                // Получаем действие активации с файлом для этого пинкода
                                $activationAction = $pincode->actions()
                                    ->where('action_type', 'активирован')
                                    ->whereNotNull('file_data')
                                    ->latest()
                                    ->first();
                            @endphp
                            
                            @if($activationAction && $activationAction->file_data)
                                <a href="{{ route('pincodes.download-file', $activationAction->id) }}" 
                                   class="btn btn-sm btn-outline-success download-file-link"
                                   title="Скачать {{ $activationAction->file_name }}">
                                    <i class="bi bi-download"></i> {{ $activationAction->file_name }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary change-status" 
                                    data-pincode-id="{{ $pincode->id }}"
                                    data-current-status="{{ $pincode->status }}"
                                    data-device-info="{{ $pincode->device_information ?? '' }}">
                                Изменить статус
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления пинкодов -->
<div class="modal fade" id="addPincodesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить пинкоды</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addPincodesForm">
                <div class="modal-body">
                    <input type="hidden" name="license_id" value="{{ $license->id }}">
                    @csrf
                    
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
                    <button type="submit" class="btn btn-primary">Добавить пинкоды</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно изменения статуса -->
<div class="modal fade" id="changeStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Изменение статуса пинкода</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="changeStatusForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="change_pincode_id" name="pincode_id">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="change_status" class="form-label">Статус</label>
                        <select class="form-select" id="change_status" name="status" required>
                            <option value="nonactivated">Не активирован</option>
                            <option value="active">Активирован</option>
                            <option value="used">Использован</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="deviceInfoSection" style="display: none;">
                        <label for="change_device_information" class="form-label">Информация об устройстве *</label>
                        <textarea class="form-control" id="change_device_information" name="device_information" 
                                  rows="3" placeholder="Информация об устройстве, на котором активируется пинкод"></textarea>
                        <div class="form-text">Обязательно для заполнения при активации</div>
                    </div>
                    
                    <div class="mb-3" id="fileUploadSection" style="display: none;">
                        <label for="change_file" class="form-label">Файл активации</label>
                        <input type="file" class="form-control" id="change_file" name="file" 
                               accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.zip,.rar,.liq">
                        <div class="form-text">Загрузите файл, связанный с активацией (максимум 10MB)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="change_comment" class="form-label">Комментарий</label>
                        <textarea class="form-control" id="change_comment" name="comment" 
                                  rows="3" placeholder="Дополнительный комментарий к действию"></textarea>
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
    // Обработчик для кнопки изменения статуса
    $(document).on('click', '.change-status', function() {
        const pincodeId = $(this).data('pincode-id');
        const currentStatus = $(this).data('current-status');
        const deviceInfo = $(this).data('device-info');
        
        $('#change_pincode_id').val(pincodeId);
        $('#change_status').val(currentStatus);
        $('#change_device_information').val(deviceInfo || '');
        
        // Сбрасываем файловый инпут
        $('#change_file').val('');
        
        // Показываем/скрываем поля в зависимости от выбранного статуса
        toggleFieldsBasedOnStatus(currentStatus);
        
        $('#changeStatusModal').modal('show');
    });

    // Изменение видимости полей при смене статуса
    $('#change_status').change(function() {
        toggleFieldsBasedOnStatus($(this).val());
    });

    function toggleFieldsBasedOnStatus(status) {
        if (status === 'active') {
            $('#deviceInfoSection').show();
            $('#fileUploadSection').show();
            $('#change_device_information').attr('required', true);
        } else {
            $('#deviceInfoSection').hide();
            $('#fileUploadSection').hide();
            $('#change_device_information').removeAttr('required');
        }
    }

    // Обработка формы изменения статуса
    $('#changeStatusForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Добавляем CSRF токен в FormData
        const csrfToken = $('input[name="_token"]', this).val();
        formData.append('_token', csrfToken);
        
        // Показываем индикатор загрузки
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Сохранение...');
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: '/pincodes/change-status-with-comment',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#changeStatusModal').modal('hide');
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
                    let errorMessage = 'Произошли ошибки при сохранении:\n';
                    for (const field in errors) {
                        errorMessage += `- ${errors[field][0]}\n`;
                    }
                    showNotification(errorMessage, 'error');
                } else {
                    const errorMessage = xhr.responseJSON?.message || 'Произошла ошибка при сохранении';
                    showNotification(errorMessage, 'error');
                }
            }
        });
    });

    // Обработка формы добавления пинкодов
    $('#addPincodesForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        // Показываем индикатор загрузки
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Добавление...');
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: '/pincodes',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#addPincodesModal').modal('hide');
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
                    let errorMessage = 'Произошли ошибки при добавлении:\n';
                    for (const field in errors) {
                        errorMessage += `- ${errors[field][0]}\n`;
                    }
                    showNotification(errorMessage, 'error');
                } else {
                    showNotification('Произошла ошибка при добавлении пинкодов', 'error');
                }
            }
        });
    });

    // Обработка скачивания файла
    $(document).on('click', '.download-file', function(e) {
        e.preventDefault();
        const actionId = $(this).data('action-id');
        
        // Показываем уведомление о начале загрузки
        showNotification('Начинается загрузка файла...', 'info');
        
        // Создаем временную ссылку для скачивания
        const downloadUrl = `/actions/${actionId}/download-file`;
        
        // Создаем скрытый iframe для скачивания
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = downloadUrl;
        document.body.appendChild(iframe);
        
        // Удаляем iframe после загрузки
        setTimeout(() => {
            document.body.removeChild(iframe);
        }, 1000);
    });

    // Функция для показа уведомлений
    function showNotification(message, type = 'info') {
        // Удаляем предыдущие уведомления
        $('.alert-dismissible.position-fixed').remove();
        
        // Создаем элемент уведомления
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
        
        // Автоматически скрываем через 5 секунд
        setTimeout(() => {
            $alert.alert('close');
        }, 5000);
    }

    // Инициализация при загрузке страницы
    toggleFieldsBasedOnStatus($('#change_status').val());
});
</script>

<style>
.download-file {
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.875rem;
}
.download-file:hover {
    text-decoration: none;
}
.table th {
    font-weight: 600;
}
.code {
    font-family: 'Courier New', monospace;
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
}
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
</style>
@endsection