@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Продукты</h1>
    <div>
        @if(auth()->user()->isAdmin())
            @if(isset($showDeleted) && $showDeleted)
                <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">
                    Не удалённые записи
                </a>
            @else
                <a href="{{ route('products.deleted') }}" class="btn btn-warning me-2">
                    Удалённые записи
                </a>
            @endif
        @endif
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
            Добавить продукт
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Описание</th>
                        @if(isset($showDeleted) && $showDeleted)
                            <th>Дата удаления</th>
                            <th>Действия</th>
                        @else
                            <th>Действия</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr class="{{ isset($showDeleted) && $showDeleted ? 'table-danger' : '' }}">
                        <td>{{ $product->id }}</td>
                        <td>
                            @if(!isset($showDeleted) || !$showDeleted)
                                <span class="editable" data-field="name" data-id="{{ $product->id }}">
                                    {{ $product->name }}
                                </span>
                            @else
                                {{ $product->name }}
                            @endif
                        </td>
                        <td>
                            @if(!isset($showDeleted) || !$showDeleted)
                                <span class="editable" data-field="description" data-id="{{ $product->id }}">
                                    {{ $product->description ?? '—' }}
                                </span>
                            @else
                                {{ $product->description ?? '—' }}
                            @endif
                        </td>
                        @if(isset($showDeleted) && $showDeleted)
                            <td>{{ $product->deleted_at->timezone('Europe/Moscow')->format('d.m.Y H:i') }}</td>
                            <td>
                                <button class="btn btn-sm btn-success restore-product" data-id="{{ $product->id }}">
                                    Восстановить
                                </button>
                            </td>
                        @else
                            <td>
                                @if(auth()->user()->isAdmin())
                                    <button class="btn btn-sm btn-danger delete-product" 
                                            data-id="{{ $product->id }}" 
                                            data-name="{{ $product->name }}">
                                        Удалить
                                    </button>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления продукта -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить продукт</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="productForm">
                <div class="modal-body">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Название продукта *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Описание</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения удаления -->
<div class="modal fade" id="deleteProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить продукт <strong id="deleteProductName"></strong>?</p>
                <p class="text-muted">Продукт будет помечен как удаленный и не будет отображаться в основном списке.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmProductDelete">Удалить</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let currentProductId = null;

    // Обработка формы добавления продукта
    $('#productForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: '/products',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#productModal').modal('hide');
                    $('#productForm')[0].reset();
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
        const currentValue = $(this).text().trim();
        
        const $input = $('<input type="text" class="form-control editable-input">')
            .val(currentValue === '—' ? '' : currentValue);
        
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
                        url: `/products/${id}/update-field`,
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

    // Подтверждение удаления продукта
    $(document).on('click', '.delete-product', function() {
        currentProductId = $(this).data('id');
        const productName = $(this).data('name');
        $('#deleteProductName').text(productName);
        $('#deleteProductModal').modal('show');
    });

    $('#confirmProductDelete').click(function() {
        if (currentProductId) {
            $.ajax({
                url: `/products/${currentProductId}`,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        $('#deleteProductModal').modal('hide');
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

    // Восстановление продукта
    $(document).on('click', '.restore-product', function() {
        const productId = $(this).data('id');
        
        if (confirm('Вы уверены, что хотите восстановить этот продукт?')) {
            $.ajax({
                url: `/products/${productId}/restore`,
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
@endsection