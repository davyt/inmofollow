<x-filament-widgets::widget>

<style>
.qa-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    padding: 4px 0 8px;
}
@media (max-width: 768px) {
    .qa-grid { grid-template-columns: repeat(2, 1fr); }
}
.qa-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 18px 12px;
    background: #1a1a2e;
    border: 1px solid #2d2d42;
    border-radius: 12px;
    text-decoration: none;
    color: #94a3b8;
    font-size: 13px;
    font-weight: 600;
    transition: border-color .15s, color .15s, background .15s;
    cursor: pointer;
}
.qa-btn:hover {
    border-color: #f59e0b;
    color: #f59e0b;
    background: #1e1e35;
}
.qa-icon {
    font-size: 22px;
    line-height: 1;
}
</style>

<div class="qa-grid">
    <a href="/davyt/inbox" class="qa-btn">
        <span class="qa-icon">💬</span>
        <span>Conversaciones</span>
    </a>
    <a href="/davyt/pipeline" class="qa-btn">
        <span class="qa-icon">📋</span>
        <span>Pipeline</span>
    </a>
    <a href="/davyt/leads/create" class="qa-btn">
        <span class="qa-icon">➕</span>
        <span>Nuevo lead</span>
    </a>
    <a href="/davyt/broadcasts" class="qa-btn">
        <span class="qa-icon">📢</span>
        <span>Broadcast</span>
    </a>
</div>

</x-filament-widgets::widget>
