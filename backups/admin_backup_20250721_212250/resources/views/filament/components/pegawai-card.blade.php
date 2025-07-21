@php
    $record = $getRecord();
@endphp

<!-- User Card inspired by provided HTML -->
<div class="user-fi-card group relative overflow-hidden">
    <!-- Timestamp -->
    <div class="timestamp">
        {{ $record->created_at?->diffForHumans() ?? 'Just now' }}
    </div>
    
    <!-- Card Header -->
    <div class="fi-card-header">
        <div class="avatar">
            @if($record->foto)
                <img 
                    src="{{ asset('storage/' . $record->foto) }}" 
                    alt="{{ $record->nama_lengkap }}"
                    class="w-full h-full object-cover rounded-full"
                />
            @else
                {{ strtoupper(substr($record->nama_lengkap, 0, 2)) }}
            @endif
        </div>
        <div class="user-info">
            <h3>{{ ucwords(strtolower($record->nama_lengkap)) }}</h3>
            <div class="user-id">ID: {{ $record->id }}</div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="details-grid">
        <!-- NIK -->
        <div class="detail-item">
            <svg class="detail-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a2 2 0 012 2m-2-2a2 2 0 00-2 2m2-2V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m0 4a2 2 0 012 2v4a2 2 0 002 2h4a2 2 0 002-2v-4a2 2 0 012-2m-6 0h4"/>
            </svg>
            <div class="detail-content">
                <div class="detail-label">NIK</div>
                <div class="detail-value">{{ $record->nik }}</div>
            </div>
        </div>

        <!-- Jabatan -->
        <div class="detail-item">
            <svg class="detail-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6.5a1.5 1.5 0 01-1.5 1.5H6.5A1.5 1.5 0 015 15V8a2 2 0 012-2V6"/>
            </svg>
            <div class="detail-content">
                <div class="detail-label">Jabatan</div>
                <div class="detail-value">{{ $record->jabatan }}</div>
            </div>
        </div>

        <!-- Role/Jenis Pegawai -->
        <div class="detail-item">
            <svg class="detail-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <div class="detail-content">
                <div class="detail-label">Role</div>
                <div class="status-badge {{ $record->jenis_pegawai === 'Paramedis' ? 'admin' : '' }}">
                    <div class="status-dot"></div>
                    {{ $record->jenis_pegawai }}
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="detail-item">
            <svg class="detail-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="detail-content">
                <div class="detail-label">Status</div>
                <div class="status-badge {{ !$record->aktif ? 'inactive' : '' }}">
                    <div class="status-dot"></div>
                    {{ $record->aktif ? 'Aktif' : 'Nonaktif' }}
                </div>
            </div>
        </div>

        <!-- Account Status -->
        @if($record->user_id || $record->has_login_account)
            <div class="detail-item">
                <svg class="detail-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <div class="detail-content">
                    <div class="detail-label">Account</div>
                    <div class="detail-value">
                        @if($record->user_id)
                            User: {{ $record->user?->username ?? 'No username' }}
                        @else
                            Login: {{ $record->username ?? 'No username' }}
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Action Button - akan di-handle oleh Filament Actions -->
    <div class="action-button-placeholder">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
        </svg>
        Actions Available
    </div>
</div>

<style>
    .user-fi-card {
        background: rgba(30, 41, 59, 0.9);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(148, 163, 184, 0.1);
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .user-fi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, #06b6d4 0%, #3b82f6 100%);
    }

    .user-fi-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
        border-color: rgba(148, 163, 184, 0.2);
    }

    .fi-card-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1rem;
        position: relative;
        border: 2px solid rgba(148, 163, 184, 0.2);
        overflow: hidden;
    }

    .avatar::after {
        content: '';
        position: absolute;
        bottom: 1px;
        right: 1px;
        width: 12px;
        height: 12px;
        background: #10b981;
        border: 2px solid white;
        border-radius: 50%;
    }

    .user-info h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #f8fafc;
        margin-bottom: 0.25rem;
    }

    .user-id {
        color: #94a3b8;
        font-size: 0.8rem;
        font-family: 'JetBrains Mono', monospace;
    }

    .details-grid {
        display: grid;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .detail-item {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.6rem;
        background: rgba(51, 65, 85, 0.5);
        border: 1px solid rgba(148, 163, 184, 0.1);
        border-radius: 8px;
        transition: all 0.2s;
    }

    .detail-item:hover {
        background: rgba(51, 65, 85, 0.7);
        border-color: rgba(148, 163, 184, 0.2);
    }

    .detail-icon {
        width: 16px;
        height: 16px;
        color: #06b6d4;
        flex-shrink: 0;
    }

    .detail-content {
        flex: 1;
    }

    .detail-label {
        font-size: 0.7rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.2rem;
    }

    .detail-value {
        font-weight: 500;
        color: #f1f5f9;
        font-size: 0.9rem;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.8rem;
        background: rgba(16, 185, 129, 0.2);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.3);
        border-radius: 16px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .status-badge.admin {
        background: rgba(245, 158, 11, 0.2);
        color: #fbbf24;
        border-color: rgba(245, 158, 11, 0.3);
    }

    .status-badge.inactive {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
        border-color: rgba(239, 68, 68, 0.3);
    }

    .status-dot {
        width: 6px;
        height: 6px;
        background: #10b981;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .status-badge.admin .status-dot {
        background: #f59e0b;
    }

    .status-badge.inactive .status-dot {
        background: #ef4444;
    }

    .action-button-placeholder {
        width: 100%;
        padding: 0.7rem 1.2rem;
        background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        font-size: 0.9rem;
    }

    .action-button-placeholder:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 12px rgba(6, 182, 212, 0.3);
    }

    .timestamp {
        position: absolute;
        top: 0.8rem;
        right: 0.8rem;
        background: rgba(51, 65, 85, 0.8);
        color: #94a3b8;
        padding: 0.2rem 0.6rem;
        border-radius: 16px;
        font-size: 0.7rem;
        border: 1px solid rgba(148, 163, 184, 0.2);
    }

    /* Dark mode compatibility */
    .dark .user-fi-card {
        background: rgba(30, 41, 59, 0.9);
        border-color: rgba(148, 163, 184, 0.1);
    }

    .dark .user-info h3 {
        color: #f8fafc;
    }

    .dark .detail-value {
        color: #f1f5f9;
    }

    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .user-fi-card {
            padding: 1rem;
        }
        
        .fi-card-header {
            gap: 0.6rem;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            font-size: 0.9rem;
        }
        
        .user-info h3 {
            font-size: 1rem;
        }
        
        .user-id {
            font-size: 0.75rem;
        }
        
        .details-grid {
            gap: 0.6rem;
        }
        
        .detail-item {
            padding: 0.5rem;
        }
        
        .detail-icon {
            width: 14px;
            height: 14px;
        }
        
        .detail-label {
            font-size: 0.65rem;
        }
        
        .detail-value {
            font-size: 0.85rem;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.3rem 0.6rem;
        }
        
        .action-button-placeholder {
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
        }
        
        .timestamp {
            font-size: 0.65rem;
            padding: 0.15rem 0.5rem;
        }
    }
</style>