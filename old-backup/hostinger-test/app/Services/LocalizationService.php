<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Exception;

class LocalizationService
{
    protected array $supportedLocales = [
        'id' => [
            'name' => 'Bahasa Indonesia',
            'native' => 'Bahasa Indonesia',
            'flag' => '🇮🇩',
            'direction' => 'ltr',
            'currency' => 'IDR',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'number_format' => [
                'decimal_separator' => ',',
                'thousands_separator' => '.',
                'decimals' => 0,
            ],
        ],
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'flag' => '🇺🇸',
            'direction' => 'ltr',
            'currency' => 'USD',
            'date_format' => 'm/d/Y',
            'time_format' => 'g:i A',
            'number_format' => [
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'decimals' => 2,
            ],
        ],
        'ar' => [
            'name' => 'العربية',
            'native' => 'العربية',
            'flag' => '🇸🇦',
            'direction' => 'rtl',
            'currency' => 'SAR',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'number_format' => [
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'decimals' => 2,
            ],
        ],
        'ms' => [
            'name' => 'Bahasa Melayu',
            'native' => 'Bahasa Melayu',
            'flag' => '🇲🇾',
            'direction' => 'ltr',
            'currency' => 'MYR',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'number_format' => [
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'decimals' => 2,
            ],
        ],
    ];

    protected string $defaultLocale = 'id';
    protected string $fallbackLocale = 'id';

    /**
     * Get all supported locales
     */
    public function getSupportedLocales(): array
    {
        return $this->supportedLocales;
    }

    /**
     * Get current locale
     */
    public function getCurrentLocale(): string
    {
        return App::getLocale();
    }

    /**
     * Set application locale
     */
    public function setLocale(string $locale): bool
    {
        if (!$this->isSupported($locale)) {
            Log::warning('LocalizationService: Unsupported locale requested', [
                'requested_locale' => $locale,
                'supported_locales' => array_keys($this->supportedLocales),
            ]);
            return false;
        }

        try {
            App::setLocale($locale);
            Session::put('locale', $locale);
            
            // Store user preference if authenticated
            if (auth()->check()) {
                auth()->user()->update(['locale' => $locale]);
            }

            Log::info('LocalizationService: Locale changed', [
                'new_locale' => $locale,
                'user_id' => auth()->id(),
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('LocalizationService: Failed to set locale', [
                'locale' => $locale,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if locale is supported
     */
    public function isSupported(string $locale): bool
    {
        return array_key_exists($locale, $this->supportedLocales);
    }

    /**
     * Get locale configuration
     */
    public function getLocaleConfig(string $locale = null): array
    {
        $locale = $locale ?? $this->getCurrentLocale();
        return $this->supportedLocales[$locale] ?? $this->supportedLocales[$this->defaultLocale];
    }

    /**
     * Format number according to locale
     */
    public function formatNumber(float $number, string $locale = null, int $decimals = null): string
    {
        $config = $this->getLocaleConfig($locale);
        $decimals = $decimals ?? $config['number_format']['decimals'];

        return number_format(
            $number,
            $decimals,
            $config['number_format']['decimal_separator'],
            $config['number_format']['thousands_separator']
        );
    }

    /**
     * Format currency according to locale
     */
    public function formatCurrency(float $amount, string $locale = null, bool $showSymbol = true): string
    {
        $config = $this->getLocaleConfig($locale);
        $formattedNumber = $this->formatNumber($amount, $locale);

        if (!$showSymbol) {
            return $formattedNumber;
        }

        $currency = $config['currency'];
        
        return match ($currency) {
            'IDR' => 'Rp ' . $formattedNumber,
            'USD' => '$' . $formattedNumber,
            'SAR' => $formattedNumber . ' ريال',
            'MYR' => 'RM ' . $formattedNumber,
            default => $currency . ' ' . $formattedNumber,
        };
    }

    /**
     * Format date according to locale
     */
    public function formatDate($date, string $locale = null): string
    {
        if (!$date) return '';
        
        $config = $this->getLocaleConfig($locale);
        $carbonDate = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
        
        return $carbonDate->format($config['date_format']);
    }

    /**
     * Format time according to locale
     */
    public function formatTime($time, string $locale = null): string
    {
        if (!$time) return '';
        
        $config = $this->getLocaleConfig($locale);
        $carbonTime = is_string($time) ? \Carbon\Carbon::parse($time) : $time;
        
        return $carbonTime->format($config['time_format']);
    }

    /**
     * Format datetime according to locale
     */
    public function formatDateTime($datetime, string $locale = null): string
    {
        if (!$datetime) return '';
        
        $config = $this->getLocaleConfig($locale);
        $carbonDateTime = is_string($datetime) ? \Carbon\Carbon::parse($datetime) : $datetime;
        
        return $carbonDateTime->format($config['date_format'] . ' ' . $config['time_format']);
    }

    /**
     * Get translated text with fallback
     */
    public function trans(string $key, array $replace = [], string $locale = null): string
    {
        $locale = $locale ?? $this->getCurrentLocale();
        
        // Try to get translation for current locale
        $translation = trans($key, $replace, $locale);
        
        // If translation not found and not using fallback locale, try fallback
        if ($translation === $key && $locale !== $this->fallbackLocale) {
            $translation = trans($key, $replace, $this->fallbackLocale);
        }
        
        return $translation;
    }

    /**
     * Get financial terminology translations
     */
    public function getFinancialTerms(string $locale = null): array
    {
        $locale = $locale ?? $this->getCurrentLocale();
        
        return Cache::remember("financial_terms_{$locale}", 3600, function () use ($locale) {
            $termsFile = resource_path("lang/{$locale}/financial.php");
            
            if (File::exists($termsFile)) {
                return include $termsFile;
            }
            
            // Fallback to default locale
            $fallbackFile = resource_path("lang/{$this->fallbackLocale}/financial.php");
            return File::exists($fallbackFile) ? include $fallbackFile : [];
        });
    }

    /**
     * Get bendahara-specific translations
     */
    public function getBendaharaTerms(string $locale = null): array
    {
        $locale = $locale ?? $this->getCurrentLocale();
        
        return Cache::remember("bendahara_terms_{$locale}", 3600, function () use ($locale) {
            $termsFile = resource_path("lang/{$locale}/bendahara.php");
            
            if (File::exists($termsFile)) {
                return include $termsFile;
            }
            
            // Fallback to default locale
            $fallbackFile = resource_path("lang/{$this->fallbackLocale}/bendahara.php");
            return File::exists($fallbackFile) ? include $fallbackFile : [];
        });
    }

    /**
     * Detect user's preferred locale
     */
    public function detectPreferredLocale(): string
    {
        // Check user preference
        if (auth()->check() && auth()->user()->locale) {
            return auth()->user()->locale;
        }

        // Check session
        if (Session::has('locale')) {
            return Session::get('locale');
        }

        // Check browser language
        $acceptLanguage = request()->header('Accept-Language');
        if ($acceptLanguage) {
            $preferredLocale = $this->parseAcceptLanguage($acceptLanguage);
            if ($this->isSupported($preferredLocale)) {
                return $preferredLocale;
            }
        }

        return $this->defaultLocale;
    }

    /**
     * Parse Accept-Language header
     */
    protected function parseAcceptLanguage(string $acceptLanguage): string
    {
        $languages = explode(',', $acceptLanguage);
        
        foreach ($languages as $language) {
            $lang = trim(explode(';', $language)[0]);
            $primaryLang = explode('-', $lang)[0];
            
            if ($this->isSupported($primaryLang)) {
                return $primaryLang;
            }
        }
        
        return $this->defaultLocale;
    }

    /**
     * Generate language files for bendahara module
     */
    public function generateLanguageFiles(): array
    {
        $results = [];
        
        foreach ($this->supportedLocales as $locale => $config) {
            try {
                $results[$locale] = $this->generateLanguageFile($locale);
            } catch (Exception $e) {
                Log::error('LocalizationService: Failed to generate language file', [
                    'locale' => $locale,
                    'error' => $e->getMessage(),
                ]);
                $results[$locale] = false;
            }
        }
        
        return $results;
    }

    /**
     * Generate language file for specific locale
     */
    protected function generateLanguageFile(string $locale): bool
    {
        $langPath = resource_path("lang/{$locale}");
        
        if (!File::exists($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }

        // Generate bendahara.php
        $bendaharaTerms = $this->getBendaharaTranslations($locale);
        File::put(
            $langPath . '/bendahara.php',
            "<?php\n\nreturn " . var_export($bendaharaTerms, true) . ";\n"
        );

        // Generate financial.php
        $financialTerms = $this->getFinancialTranslations($locale);
        File::put(
            $langPath . '/financial.php',
            "<?php\n\nreturn " . var_export($financialTerms, true) . ";\n"
        );

        return true;
    }

    /**
     * Get bendahara translations for locale
     */
    protected function getBendaharaTranslations(string $locale): array
    {
        return match ($locale) {
            'id' => [
                'dashboard' => 'Dashboard Bendahara',
                'validation_queue' => 'Antrian Validasi',
                'financial_overview' => 'Ikhtisar Keuangan',
                'cash_flow' => 'Arus Kas',
                'budget_tracking' => 'Pelacakan Anggaran',
                'approve' => 'Setujui',
                'reject' => 'Tolak',
                'pending' => 'Menunggu',
                'approved' => 'Disetujui',
                'rejected' => 'Ditolak',
                'revenue' => 'Pendapatan',
                'expense' => 'Pengeluaran',
                'balance' => 'Saldo',
                'forecast' => 'Proyeksi',
                'analytics' => 'Analitik',
                'notifications' => 'Notifikasi',
                'reports' => 'Laporan',
                'settings' => 'Pengaturan',
            ],
            'en' => [
                'dashboard' => 'Treasurer Dashboard',
                'validation_queue' => 'Validation Queue',
                'financial_overview' => 'Financial Overview',
                'cash_flow' => 'Cash Flow',
                'budget_tracking' => 'Budget Tracking',
                'approve' => 'Approve',
                'reject' => 'Reject',
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'revenue' => 'Revenue',
                'expense' => 'Expense',
                'balance' => 'Balance',
                'forecast' => 'Forecast',
                'analytics' => 'Analytics',
                'notifications' => 'Notifications',
                'reports' => 'Reports',
                'settings' => 'Settings',
            ],
            'ar' => [
                'dashboard' => 'لوحة أمين الصندوق',
                'validation_queue' => 'قائمة انتظار التحقق',
                'financial_overview' => 'نظرة عامة مالية',
                'cash_flow' => 'التدفق النقدي',
                'budget_tracking' => 'تتبع الميزانية',
                'approve' => 'موافقة',
                'reject' => 'رفض',
                'pending' => 'في الانتظار',
                'approved' => 'مُوافق عليه',
                'rejected' => 'مرفوض',
                'revenue' => 'الإيرادات',
                'expense' => 'المصروفات',
                'balance' => 'الرصيد',
                'forecast' => 'التنبؤ',
                'analytics' => 'التحليلات',
                'notifications' => 'الإشعارات',
                'reports' => 'التقارير',
                'settings' => 'الإعدادات',
            ],
            'ms' => [
                'dashboard' => 'Dashboard Bendahari',
                'validation_queue' => 'Barisan Pengesahan',
                'financial_overview' => 'Gambaran Kewangan',
                'cash_flow' => 'Aliran Tunai',
                'budget_tracking' => 'Penjejakan Belanjawan',
                'approve' => 'Lulus',
                'reject' => 'Tolak',
                'pending' => 'Menunggu',
                'approved' => 'Diluluskan',
                'rejected' => 'Ditolak',
                'revenue' => 'Pendapatan',
                'expense' => 'Perbelanjaan',
                'balance' => 'Baki',
                'forecast' => 'Ramalan',
                'analytics' => 'Analitik',
                'notifications' => 'Pemberitahuan',
                'reports' => 'Laporan',
                'settings' => 'Tetapan',
            ],
            default => [],
        };
    }

    /**
     * Get financial translations for locale
     */
    protected function getFinancialTranslations(string $locale): array
    {
        return match ($locale) {
            'id' => [
                'income' => 'Pemasukan',
                'outcome' => 'Pengeluaran',
                'profit' => 'Keuntungan',
                'loss' => 'Kerugian',
                'total' => 'Total',
                'amount' => 'Jumlah',
                'date' => 'Tanggal',
                'description' => 'Deskripsi',
                'category' => 'Kategori',
                'status' => 'Status',
                'created_by' => 'Dibuat Oleh',
                'validated_by' => 'Divalidasi Oleh',
                'monthly_report' => 'Laporan Bulanan',
                'yearly_report' => 'Laporan Tahunan',
                'cash_in_hand' => 'Kas di Tangan',
                'bank_balance' => 'Saldo Bank',
                'accounts_receivable' => 'Piutang',
                'accounts_payable' => 'Hutang',
            ],
            'en' => [
                'income' => 'Income',
                'outcome' => 'Expense',
                'profit' => 'Profit',
                'loss' => 'Loss',
                'total' => 'Total',
                'amount' => 'Amount',
                'date' => 'Date',
                'description' => 'Description',
                'category' => 'Category',
                'status' => 'Status',
                'created_by' => 'Created By',
                'validated_by' => 'Validated By',
                'monthly_report' => 'Monthly Report',
                'yearly_report' => 'Yearly Report',
                'cash_in_hand' => 'Cash in Hand',
                'bank_balance' => 'Bank Balance',
                'accounts_receivable' => 'Accounts Receivable',
                'accounts_payable' => 'Accounts Payable',
            ],
            'ar' => [
                'income' => 'الدخل',
                'outcome' => 'المصروفات',
                'profit' => 'الربح',
                'loss' => 'الخسارة',
                'total' => 'المجموع',
                'amount' => 'المبلغ',
                'date' => 'التاريخ',
                'description' => 'الوصف',
                'category' => 'الفئة',
                'status' => 'الحالة',
                'created_by' => 'أنشأ بواسطة',
                'validated_by' => 'تم التحقق بواسطة',
                'monthly_report' => 'التقرير الشهري',
                'yearly_report' => 'التقرير السنوي',
                'cash_in_hand' => 'النقد في الصندوق',
                'bank_balance' => 'رصيد البنك',
                'accounts_receivable' => 'حسابات القبض',
                'accounts_payable' => 'حسابات الدفع',
            ],
            'ms' => [
                'income' => 'Pendapatan',
                'outcome' => 'Perbelanjaan',
                'profit' => 'Keuntungan',
                'loss' => 'Kerugian',
                'total' => 'Jumlah',
                'amount' => 'Amaun',
                'date' => 'Tarikh',
                'description' => 'Penerangan',
                'category' => 'Kategori',
                'status' => 'Status',
                'created_by' => 'Dicipta Oleh',
                'validated_by' => 'Disahkan Oleh',
                'monthly_report' => 'Laporan Bulanan',
                'yearly_report' => 'Laporan Tahunan',
                'cash_in_hand' => 'Tunai di Tangan',
                'bank_balance' => 'Baki Bank',
                'accounts_receivable' => 'Akaun Belum Terima',
                'accounts_payable' => 'Akaun Belum Bayar',
            ],
            default => [],
        };
    }

    /**
     * Get RTL locales
     */
    public function getRTLLocales(): array
    {
        return array_keys(array_filter($this->supportedLocales, fn($config) => $config['direction'] === 'rtl'));
    }

    /**
     * Check if current locale is RTL
     */
    public function isRTL(string $locale = null): bool
    {
        $config = $this->getLocaleConfig($locale);
        return $config['direction'] === 'rtl';
    }

    /**
     * Get locale flag emoji
     */
    public function getLocaleFlag(string $locale = null): string
    {
        $config = $this->getLocaleConfig($locale);
        return $config['flag'] ?? '🌐';
    }

    /**
     * Clear localization cache
     */
    public function clearCache(): bool
    {
        try {
            foreach ($this->supportedLocales as $locale => $config) {
                Cache::forget("financial_terms_{$locale}");
                Cache::forget("bendahara_terms_{$locale}");
            }
            
            Log::info('LocalizationService: Cache cleared successfully');
            return true;
            
        } catch (Exception $e) {
            Log::error('LocalizationService: Failed to clear cache', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}