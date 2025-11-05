@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>Пинкоды лицензии</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('organizations.index') }}">Организации</a></li>
                <li class="breadcrumb-item"><a href="{{ route('licenses.organization', $license->organization) }}">Лицензии {{ $license->organization->name }}</a></li>
                <li class="breadcrumb-item active">Пинкоды</li>
            </ol>
        </nav>
        <p class="mb-0">Лицензия: {{ $license->product->name }} (№{{ $license->number }})</p>
        @if($license->max_count)
        <p class="mb-0">Макс. пользователей: {{ $license->max_count }}</p>
        @endif
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPincodeModal">
            Добавить пинкоды
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped" id="pincodesTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Значение</th>
                <th>Тип</th>
                <th>Статус</th>
                <th>Устройство</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pincodes as $pincode)
            <tr>
                <td>{{ $pincode->id }}</td>
                <td>
                    <code class="bg-light px-2 py-1 rounded">{{ $pincode->value }}</code>
                </td>
                <td>
                    @if($pincode->type == 'single')
                        <span class="badge bg-info">Однопользовательский</span>
                    @else
                        <span class="badge bg-warning">Многопользовательский</span>
                    @endif
                </td>
                <td class="editable-status" 
                    data-id="{{ $pincode->id }}"
                    data-value="{{ $pincode->status }}">
                    @if($pincode->status == 'nonactivated')
                        <span class="badge bg-secondary">Неактивирован</span>
                    @elseif($pincode->status == 'active')
                        <span class="badge bg-success">Активен</span>
                    @else
                        <span class="badge bg-danger">Использован</span>
                    @endif
                </td>
                <td>
                    @if($pincode->device_information)
                        <div class="device-info-text" style="max-width: 200px;">
                            {{ $pincode->device_information }}
                        </div>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-primary change-status-btn" 
                            data-bs-toggle="modal" 
                            data-bs-target="#changeStatusModal"
                            data-pincode-id="{{ $pincode->id }}"
                            data-current-status="{{ $pincode->status }}"
                            data-pincode-value="{{ $pincode->value }}"
                            data-license-number="{{ $pincode->license->number }}"
                            data-device-information="{{ $pincode->device_information ?? '' }}">
                        Изменить статус
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal добавления пинкодов -->
<div class="modal fade" id="addPincodeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить пинкоды</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addPincodeForm">
                <input type="hidden" name="license_id" value="{{ $license->id }}">
                <div class="modal-body">
                    <!-- Многопользовательские пинкоды -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Многопользовательские пинкоды</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="multi_pincodes" class="form-label">Пинкоды</label>
                                <textarea class="form-control" id="multi_pincodes" name="multi_pincodes" 
                                          rows="4" placeholder="Введите пинкоды, каждый с новой строки"></textarea>
                                <div class="form-text">Необязательное поле. Каждый пинкод с новой строки</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Однопользовательские пинкоды -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Однопользовательские пинкоды</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="single_pincodes" class="form-label">Пинкоды</label>
                                <textarea class="form-control" id="single_pincodes" name="single_pincodes" 
                                          rows="4" placeholder="Введите пинкоды, каждый с новой строки"></textarea>
                                <div class="form-text">Необязательное поле. Каждый пинкод с новой строки</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
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

<!-- Modal изменения статуса с комментарием и устройством -->
<div class="modal fade" id="changeStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Изменение статуса пинкода</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="changeStatusForm">
                <input type="hidden" id="change_status_pincode_id" name="pincode_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Пинкод</label>
                        <input type="text" class="form-control" id="display_pincode_value" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Номер лицензии</label>
                        <input type="text" class="form-control" id="display_license_number" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="new_status" class="form-label">Новый статус *</label>
                        <select class="form-select" id="new_status" name="status" required>
                            <option value="nonactivated">Неактивирован</option>
                            <option value="active">Активен</option>
                            <option value="used">Использован</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3 device-info-container" style="display: none;">
                        <label for="device_information" class="form-label">Информация об устройстве</label>
                        <textarea class="form-control" id="device_information" name="device_information" 
                                  rows="3" placeholder="Введите информацию об устройстве (модель, серийный номер и т.д.)"></textarea>
                        <div class="form-text">Обязательно для заполнения при активации пинкода</div>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="comment" class="form-label">Комментарий</label>
                        <textarea class="form-control" id="comment" name="comment" rows="4" 
                                  placeholder="Введите комментарий к изменению статуса"></textarea>
                        <div class="form-text">Комментарий будет сохранен в истории действий</div>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Изменить статус</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Inline редактирование статуса пинкода (старое, можно оставить или удалить)
    $('#pincodesTable').on('dblclick', '.editable-status', function() {
        const $cell = $(this);
        const id = $cell.data('id');
        const value = $cell.data('value');
        
        const $select = $(`
            <select class="form-select editable-input">
                <option value="nonactivated" ${value === 'nonactivated' ? 'selected' : ''}>Неактивирован</option>
                <option value="active" ${value === 'active' ? 'selected' : ''}>Активен</option>
                <option value="used" ${value === 'used' ? 'selected' : ''}>Использован</option>
            </select>
        `);
        
        $select.css({
            top: $cell.offset().top,
            left: $cell.offset().left,
            width: $cell.outerWidth(),
            height: $cell.outerHeight()
        });
        
        $('body').append($select);
        $select.focus();
        
        function saveValue() {
            const newValue = $select.val();
            if (newValue !== value) {
                $.ajax({
                    url: `/pincodes/${id}/update-status`,
                    method: 'POST',
                    data: {
                        status: newValue,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            $cell.data('value', newValue);
                            updateStatusBadge($cell, newValue);
                            showNotification('Статус обновлен', 'success');
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        if (response && response.errors) {
                            showNotification(Object.values(response.errors)[0][0], 'error');
                        } else {
                            showNotification('Ошибка обновления', 'error');
                        }
                    }
                });
            }
            $select.remove();
        }
        
        $select.on('keydown', function(e) {
            if (e.key === 'Enter') saveValue();
            if (e.key === 'Escape') $select.remove();
        });
        
        $select.on('blur', function() {
            saveValue();
        });
    });

    // Обработка кнопки изменения статуса с комментарием
    $('#pincodesTable').on('click', '.change-status-btn', function() {
        const pincodeId = $(this).data('pincode-id');
        const currentStatus = $(this).data('current-status');
        const pincodeValue = $(this).data('pincode-value');
        const licenseNumber = $(this).data('license-number');
        const deviceInformation = $(this).data('device-information') || ''; // Гарантируем строку

        console.log('Opening status change modal:', {
            pincodeId,
            currentStatus,
            pincodeValue,
            licenseNumber,
            deviceInformation
        });

        $('#change_status_pincode_id').val(pincodeId);
        $('#display_pincode_value').val(pincodeValue);
        $('#display_license_number').val(licenseNumber);
        $('#new_status').val(currentStatus);
        $('#device_information').val(deviceInformation); // Всегда устанавливаем значение

        // Управляем видимостью поля устройства
        const $deviceContainer = $('.device-info-container');

        if (currentStatus === 'active') {
            $deviceContainer.show();
        } else {
            $deviceContainer.hide();
        }

        // Устанавливаем комментарий по умолчанию
        updateDefaultComment(currentStatus);
    });

    // Показываем/скрываем поле устройства в зависимости от статуса
    $('#new_status').on('change', function() {
        const status = $(this).val();
        const $deviceContainer = $('.device-info-container');
        const $deviceField = $('#device_information');

        if (status === 'active') {
            $deviceContainer.show();

            // Если пинкод уже был активирован и есть информация об устройстве, используем её
            const currentDeviceInfo = $deviceField.val();
            if (!currentDeviceInfo) {
                // Получаем device-information из data-атрибута кнопки
                const pincodeId = $('#change_status_pincode_id').val();
                const deviceInfo = $(`button[data-pincode-id="${pincodeId}"]`).data('device-information');
                if (deviceInfo) {
                    $deviceField.val(deviceInfo);
                }
            }
        } else {
            $deviceContainer.hide();
            // Не очищаем поле устройства, чтобы сохранить данные при смене статуса
        }

        // Автоматическое обновление комментария при изменении статуса
        updateDefaultComment(status);
    });

    function updateDefaultComment(status) {
        let defaultComment = '';

        switch (status) {
            case 'nonactivated':
                defaultComment = 'Пинкод добавлен в систему.';
                break;
            case 'active':
                defaultComment = 'Пинкод активирован для использования.';
                break;
            case 'used':
                defaultComment = 'Пинкод дезактивирован.';
                break;
        }

        // Обновляем комментарий только если пользователь не менял его вручную
        const currentComment = $('#comment').val();
        if (!currentComment || 
            currentComment.includes('Пинкод добавлен') || 
            currentComment.includes('Пинкод активирован') || 
            currentComment.includes('Пинкод дезактивирован')) {
            $('#comment').val(defaultComment);
        }
    }

    // Клиентская валидация перед отправкой
    function validateForm() {
        const status = $('#new_status').val();
        const deviceInfo = $('#device_information').val();

        if (status === 'active' && !deviceInfo.trim()) {
            $('#device_information').addClass('is-invalid');
            $('#device_information').siblings('.invalid-feedback').text('Информация об устройстве обязательна при активации пинкода');
            return false;
        }

        return true;
    }


    // Обработка формы изменения статуса с комментарием
    $('#changeStatusForm').on('submit', function(e) {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.text();

        $submitBtn.prop('disabled', true).text('Сохранение...');
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');

        // Всегда отправляем device_information как строку, даже если пустую
        const deviceInfo = $('#device_information').val() || '';

        const formData = {
            pincode_id: $('#change_status_pincode_id').val(),
            status: $('#new_status').val(),
            device_information: deviceInfo, // Всегда строка
            comment: $('#comment').val(),
            _token: '{{ csrf_token() }}'
        };

        console.log('Sending status change request:', formData);

        $.ajax({
            url: '/pincodes/change-status-with-comment',
            method: 'POST',
            data: formData,
            success: function(response) {
                console.log('Status change response:', response);
                $submitBtn.prop('disabled', false).text(originalText);

                if (response.success) {
                    $('#changeStatusModal').modal('hide');
                    showNotification(response.message, 'success');

                    // Перезагружаем страницу для обновления данных
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function(xhr) {
                console.log('Status change error:', xhr.responseJSON);
                $submitBtn.prop('disabled', false).text(originalText);
                const response = xhr.responseJSON;

                if (response && response.errors) {
                    for (const field in response.errors) {
                        if (field === 'device_information') {
                            $('#device_information').addClass('is-invalid');
                            $('#device_information').siblings('.invalid-feedback').text(response.errors[field][0]);
                        } else {
                            $(`#${field}`).addClass('is-invalid');
                            $(`#${field}`).siblings('.invalid-feedback').text(response.errors[field][0]);
                        }
                    }
                    showNotification('Исправьте ошибки в форме', 'error');
                } else {
                    showNotification(response?.message || 'Ошибка сети', 'error');
                }
            }
        });
    });

    // Добавление пинкодов
    $('#addPincodeForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            license_id: {{ $license->id }},
            single_pincodes: $('#single_pincodes').val(),
            multi_pincodes: $('#multi_pincodes').val(),
            _token: '{{ csrf_token() }}'
        };
        
        $.ajax({
            url: '/pincodes',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#addPincodeModal').modal('hide');
                    showNotification('Пинкоды успешно добавлены', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
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
    
    $('#addPincodeModal').on('hidden.bs.modal', function() {
        $('#addPincodeForm')[0].reset();
        $('#addPincodeForm .form-control').removeClass('is-invalid');
    });

    // Автоматическое обновление комментария при изменении статуса
$('#new_status').on('change', function() {
    const status = $(this).val();
    const $deviceContainer = $('.device-info-container');
    const $deviceField = $('#device_information');
    
    if (status === 'active') {
        $deviceContainer.show();
        $deviceField.prop('required', true);
    } else {
        $deviceContainer.hide();
        $deviceField.prop('required', false);
        $deviceField.val(''); // Очищаем поле при скрытии
    }
    
    // Автоматическое обновление комментария при изменении статуса
    let defaultComment = $('#comment').val();
    
    // Если комментарий еще не меняли вручную, обновляем его
    if (!defaultComment || defaultComment.includes('Пинкод') || defaultComment.includes('Добавлен') || defaultComment.includes('Активирован') || defaultComment.includes('Дезактивирован')) {
        switch (status) {
            case 'nonactivated':
                defaultComment = 'Пинкод добавлен в систему.';
                break;
            case 'active':
                defaultComment = 'Пинкод активирован для использования.';
                break;
            case 'used':
                defaultComment = 'Пинкод дезактивирован.';
                break;
        }
        $('#comment').val(defaultComment);
    }
});
});

function updateStatusBadge($cell, status) {
    let badgeClass = 'bg-secondary';
    let badgeText = 'Неактивирован';
    
    if (status === 'active') {
        badgeClass = 'bg-success';
        badgeText = 'Активен';
    } else if (status === 'used') {
        badgeClass = 'bg-danger';
        badgeText = 'Использован';
    }
    
    $cell.html(`<span class="badge ${badgeClass}">${badgeText}</span>`);
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

function updateStatusBadge($cell, status) {
    let badgeClass, badgeText;
    
    switch (status) {
        case 'nonactivated':
            badgeClass = 'bg-secondary';
            badgeText = 'Неактивирован';
            break;
        case 'active':
            badgeClass = 'bg-success';
            badgeText = 'Активен';
            break;
        case 'used':
            badgeClass = 'bg-danger';
            badgeText = 'Использован';
            break;
        default:
            badgeClass = 'bg-secondary';
            badgeText = status;
    }
    
    $cell.html(`<span class="badge ${badgeClass}">${badgeText}</span>`);
    $cell.data('value', status);
    
    // Также обновляем data-атрибут кнопки
    const pincodeId = $cell.closest('tr').find('.change-status-btn').data('pincode-id');
    $(`button[data-pincode-id="${pincodeId}"]`).data('current-status', status);
}




</script>
@endsection