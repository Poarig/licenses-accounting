@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>История действий</h1>
</div>

<div class="card">
    <div class="card-header bg-light">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="mb-0">Все действия с пинкодами</h5>
            </div>
            <div class="col-auto">
                <span class="badge bg-primary">{{ $actions->count() }} записей</span>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="80">ID</th>
                        <th width="150">Дата и время</th>
                        <th width="200">Пинкод</th>
                        <th width="150">Номер лицензии</th>
                        <th width="150">Действие</th>
                        <th width="250">Устройство</th>
                        <th width="150">Пользователь</th>
                        <th width="120">Комментарий</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($actions as $action)
                    <tr>
                        <td>
                            <span class="badge bg-secondary">#{{ $action->id }}</span>
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $action->created_at->timezone('Europe/Moscow')->format('d.m.Y H:i') }}
                            </small>
                        </td>
                        <td>
                            <code class="bg-light px-2 py-1 rounded fs-6">{{ $action->pincode->value }}</code>
                        </td>
                        <td>
                            @if($action->pincode->license)
                                <span class="fw-semibold">{{ $action->pincode->license->number }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($action->action_type == 'добавлен')
                                <span class="badge bg-success fs-6">Добавлен</span>
                            @elseif($action->action_type == 'активирован')
                                <span class="badge bg-primary fs-6">Активирован</span>
                            @elseif($action->action_type == 'дезактивирован')
                                <span class="badge bg-danger fs-6">Дезактивирован</span>
                            @else
                                <span class="badge bg-secondary fs-6">{{ $action->action_type }}</span>
                            @endif
                        </td>
                        <td>
                            @if($action->device_information)
                                <div class="device-info-text" style="max-width: 240px;">
                                    {{ $action->device_information }}
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div>
                                    <div class="fw-semibold">{{ $action->user->name }} {{ $action->user->surname }}</div>
                                    <small class="text-muted">{{ $action->user->login }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($action->comment)
                                <button class="btn btn-sm btn-outline-primary comment-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#commentModal"
                                        data-comment="{{ $action->comment }}">
                                    Комментарий
                                </button>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-clock-history" style="font-size: 2rem;"></i>
                                <p class="mt-2 mb-0">История действий пуста</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($actions->hasPages())
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Показано с {{ $actions->firstItem() }} по {{ $actions->lastItem() }} из {{ $actions->total() }} записей
            </div>
            {{ $actions->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Modal для комментария -->
<div class="modal fade" id="commentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Комментарий к действию</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="bg-light p-3 rounded">
                    <p id="commentText" class="mb-0" style="white-space: pre-wrap;"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.comment-btn {
    min-width: 100px;
}
.device-info-text {
    word-wrap: break-word;
    white-space: normal;
    font-size: 0.875rem;
}
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Обработка модального окна комментария
    $('#commentModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var comment = button.data('comment');
        var modal = $(this);
        modal.find('#commentText').text(comment);
    });
});
</script>
@endsection