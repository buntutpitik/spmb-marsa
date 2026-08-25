@extends('layouts.app')

@section('content')
<div x-data="{
    editOpen:false,
    selectedPeriod:null,
    feeDisplay:'',
    formatFee(value){
        const numeric=String(value??'').replace(/\D/g,'');
        this.feeDisplay=numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '0';
    },
    openEdit(period){
        this.selectedPeriod=period;
        this.formatFee(period.default_reenroll_fee??0);
        this.editOpen=true;
    }
}" class="space-y-8">

    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="{{ route('admin.settings.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600 hover:text-emerald-700">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Pengaturan
            </a>

            <p class="mt-4 text-sm font-semibold text-emerald-600">Sistem</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Periode SPMB</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Kelola konfigurasi periode penerimaan, biaya daftar ulang,
                tanggal pendaftaran, dan format nomor pendaftaran.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Total Periode</div>
            <div class="mt-1 text-sm font-semibold text-slate-800">{{ number_format($periods->count()) }} periode</div>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <div class="font-semibold">Terdapat data yang perlu diperbaiki.</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
        <div class="flex items-start gap-3">
            <i data-lucide="info" class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"></i>
            <div>
                <h2 class="text-sm font-semibold text-blue-900">Konfigurasi berbasis periode</h2>
                <p class="mt-1 text-sm leading-6 text-blue-800">
                    Biaya daftar ulang, tanggal pendaftaran, dan format nomor disimpan
                    pada masing-masing periode sehingga histori periode sebelumnya tetap terjaga.
                </p>
            </div>
        </div>
    </section>

    @if ($periods->isEmpty())
        <section class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                <i data-lucide="calendar-range" class="h-5 w-5"></i>
            </div>
            <h2 class="mt-4 text-sm font-semibold text-slate-700">Belum ada periode SPMB</h2>
            <p class="mt-1 text-sm text-slate-500">Periode belum tersedia di database.</p>
        </section>
    @else
        <div class="grid gap-5 xl:grid-cols-2">
            @foreach ($periods as $period)
                @php
                    $statusLabel = match ($period->status) {
                        'DRAFT' => 'Draft',
                        'OPEN' => 'Dibuka',
                        'CLOSED' => 'Ditutup',
                        default => $period->status,
                    };
                    $statusClass = match ($period->status) {
                        'DRAFT' => 'bg-slate-100 text-slate-700',
                        'OPEN' => 'bg-emerald-50 text-emerald-700',
                        'CLOSED' => 'bg-rose-50 text-rose-700',
                        default => 'bg-slate-100 text-slate-700',
                    };
                @endphp

                <section class="rounded-2xl border {{ $period->is_active ? 'border-emerald-200' : 'border-slate-200' }} bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-bold text-slate-900">{{ $period->name }}</h2>
                                @if ($period->is_active)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @endif
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-slate-500">{{ $period->school?->name ?? 'Sekolah tidak tersedia' }}</p>
                        </div>

                        <button type="button"
                            @click="openEdit(@js([
                                'id'=>$period->id,
                                'name'=>$period->name,
                                'year_start'=>$period->year_start,
                                'year_end'=>$period->year_end,
                                'registration_open'=>$period->registration_open?->format('Y-m-d'),
                                'registration_close'=>$period->registration_close?->format('Y-m-d'),
                                'status'=>$period->status,
                                'is_active'=>$period->is_active,
                                'principal_name'=>$period->principal_name,
                                'principal_nip'=>$period->principal_nip,
                                'number_prefix'=>$period->number_prefix,
                                'number_year'=>$period->number_year,
                                'number_digits'=>$period->number_digits,
                                'include_major_code'=>$period->include_major_code,
                                'default_reenroll_fee'=>$period->default_reenroll_fee,
                                'notes'=>$period->notes,
                            ]))"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                            <i data-lucide="pencil" class="h-4 w-4"></i>
                            Edit
                        </button>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Pendaftaran</div>
                            <div class="mt-1 text-sm font-medium text-slate-700">
                                {{ $period->registration_open?->format('d/m/Y') ?? '-' }} —
                                {{ $period->registration_close?->format('d/m/Y') ?? '-' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Biaya Daftar Ulang</div>
                            <div class="mt-1 text-sm font-bold text-slate-900">
                                Rp {{ number_format((int)$period->default_reenroll_fee,0,',','.') }}
                            </div>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Format Nomor</div>
                            <div class="mt-1 text-sm font-medium text-slate-700">
                                {{ $period->number_prefix }}-{{ $period->number_year }}
                                @if($period->include_major_code)-[JURUSAN]@endif
                                -{{ str_repeat('0', max(1,(int)$period->number_digits)) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Kepala Sekolah</div>
                            <div class="mt-1 text-sm font-medium text-slate-700">{{ $period->principal_name ?: '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">NIP</div>
                            <div class="mt-1 text-sm font-medium text-slate-700">{{ $period->principal_nip ?: '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tahun Ajaran</div>
                            <div class="mt-1 text-sm font-medium text-slate-700">{{ $period->year_start }}/{{ $period->year_end }}</div>
                        </div>
                    </div>

                    @if($period->notes)
                        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Catatan</div>
                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $period->notes }}</p>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    @endif

    <div x-show="editOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px]" @click="editOpen=false"></div>

        <div x-show="editOpen" x-transition.opacity.scale.95 @click.stop
            class="relative z-10 my-auto w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <form method="POST" :action="selectedPeriod ? '{{ url('/admin/pengaturan/periode') }}/'+selectedPeriod.id : '#'">
                @csrf
                @method('PUT')

                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Edit Periode SPMB</h3>
                        <p class="mt-1 text-sm text-slate-500" x-text="selectedPeriod ? selectedPeriod.name : ''"></p>
                    </div>
                    <button type="button" @click="editOpen=false"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <div class="max-h-[72vh] overflow-y-auto p-6">
                    <div class="space-y-8">

                        <section>
                            <h4 class="font-bold text-slate-900">Identitas Periode</h4>
                            <p class="mt-1 text-sm text-slate-500">Tahun ajaran dan status operasional periode.</p>

                            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Periode</label>
                                    <input type="text" name="name" x-model="selectedPeriod.name" required
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tahun Awal</label>
                                    <input type="number" name="year_start" x-model="selectedPeriod.year_start" min="2020" max="2100" required
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tahun Akhir</label>
                                    <input type="number" name="year_end" x-model="selectedPeriod.year_end" min="2020" max="2101" required
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tanggal Buka</label>
                                    <input type="date" name="registration_open" x-model="selectedPeriod.registration_open"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tanggal Tutup</label>
                                    <input type="date" name="registration_close" x-model="selectedPeriod.registration_close"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Status</label>
                                    <select name="status" x-model="selectedPeriod.status" required
                                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                        <option value="DRAFT">Draft</option>
                                        <option value="OPEN">Dibuka</option>
                                        <option value="CLOSED">Ditutup</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input type="checkbox" name="is_active" value="1" x-model="selectedPeriod.is_active"
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-800">Jadikan periode aktif</span>
                                        <span class="mt-1 block text-xs leading-5 text-slate-500">
                                            Mengaktifkan periode ini akan otomatis menonaktifkan periode lain.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </section>

                        <section class="border-t border-slate-100 pt-7">
                            <h4 class="font-bold text-slate-900">Biaya Daftar Ulang</h4>
                            <p class="mt-1 text-sm text-slate-500">
                                Nominal ini digunakan sebagai tagihan daftar ulang pada periode tersebut.
                            </p>

                            <div class="mt-4 max-w-sm">
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Biaya Daftar Ulang</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-500">Rp</span>
                                    <input type="text" inputmode="numeric" x-model="feeDisplay" @input="formatFee($event.target.value)"
                                        class="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 text-sm font-semibold focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                    <input type="hidden" name="default_reenroll_fee" :value="feeDisplay.replace(/\D/g,'') || '0'">
                                </div>
                                <p class="mt-1.5 text-xs text-slate-500">Isi 0 jika daftar ulang pada periode ini tidak berbayar.</p>
                            </div>
                        </section>

                        <section class="border-t border-slate-100 pt-7">
                            <h4 class="font-bold text-slate-900">Kepala Sekolah</h4>
                            <p class="mt-1 text-sm text-slate-500">Snapshot kepala sekolah khusus periode ini.</p>

                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Kepala Sekolah</label>
                                    <input type="text" name="principal_name" x-model="selectedPeriod.principal_name"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">NIP Kepala Sekolah</label>
                                    <input type="text" name="principal_nip" x-model="selectedPeriod.principal_nip"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                </div>
                            </div>
                        </section>

                        <section class="border-t border-slate-100 pt-7">
                            <h4 class="font-bold text-slate-900">Nomor Pendaftaran</h4>
                            <p class="mt-1 text-sm text-slate-500">Konfigurasi format nomor pendaftaran untuk periode ini.</p>

                            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Prefix</label>
                                    <input type="text" name="number_prefix" x-model="selectedPeriod.number_prefix" required
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm uppercase focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tahun Nomor</label>
                                    <input type="number" name="number_year" x-model="selectedPeriod.number_year" min="2020" max="2100" required
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Jumlah Digit</label>
                                    <input type="number" name="number_digits" x-model="selectedPeriod.number_digits" min="3" max="8" required
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                </div>
                            </div>

                            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input type="checkbox" name="include_major_code" value="1" x-model="selectedPeriod.include_major_code"
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-800">Sertakan kode jurusan</span>
                                        <span class="mt-1 block text-xs text-slate-500">
                                            Contoh:
                                            <span
                                                x-text="
                                                    (selectedPeriod.number_prefix || 'SPMB')
                                                    + '-'
                                                    + (selectedPeriod.number_year || '2027')
                                                    + '-RPL-'
                                                    + String(1).padStart(
                                                        Number(selectedPeriod.number_digits || 4),
                                                        '0'
                                                    )
                                                "
                                            ></span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </section>

                        <section class="border-t border-slate-100 pt-7">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Catatan</label>
                            <textarea name="notes" rows="4" maxlength="2000" x-model="selectedPeriod.notes"
                                placeholder="Catatan internal periode (opsional)..."
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"></textarea>
                        </section>

                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button type="button" @click="editOpen=false"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Simpan Periode
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
