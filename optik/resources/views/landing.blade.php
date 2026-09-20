<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OptikReader — Telefonla Optik Form Okuma</title>
    <meta name="description" content="Sınav formlarını webde oluşturun, yazdırın ve telefon kamerasıyla saniyeler içinde okutup puanlayın.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900">
    <!-- Üst menü -->
    <nav class="fixed top-0 inset-x-0 z-50 bg-indigo-950/90 backdrop-blur border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <svg class="w-8 h-8 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="5" cy="5" r="2.2" fill="currentColor" stroke="none"/>
                    <circle cx="12" cy="5" r="2.2"/>
                    <circle cx="19" cy="5" r="2.2"/>
                    <circle cx="5" cy="12" r="2.2"/>
                    <circle cx="12" cy="12" r="2.2" fill="currentColor" stroke="none"/>
                    <circle cx="19" cy="12" r="2.2"/>
                    <circle cx="5" cy="19" r="2.2"/>
                    <circle cx="12" cy="19" r="2.2"/>
                    <circle cx="19" cy="19" r="2.2" fill="currentColor" stroke="none"/>
                </svg>
                <span class="text-white font-bold text-lg">OptikReader</span>
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm text-indigo-100">
                <a href="#nasil-calisir" class="hover:text-white">Nasıl Çalışır?</a>
                <a href="#ozellikler" class="hover:text-white">Özellikler</a>
            </div>
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-semibold text-indigo-950 bg-white rounded-md hover:bg-indigo-100">Panele Git</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-indigo-100 hover:text-white">Giriş Yap</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-semibold text-indigo-950 bg-white rounded-md hover:bg-indigo-100">Ücretsiz Başla</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <header class="bg-indigo-950 text-white pt-32 pb-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid md:grid-cols-2 gap-12 items-center">
            <div>
                <span class="inline-block px-3 py-1 text-xs font-semibold tracking-widest uppercase bg-indigo-500/20 text-indigo-300 rounded-full">Optik form okuma</span>
                <h1 class="mt-4 text-4xl sm:text-5xl font-extrabold leading-tight">Sınav kağıtlarını telefonla saniyeler içinde okutun.</h1>
                <p class="mt-4 text-lg text-indigo-200">Formu webde oluşturun, yazdırın, mobil uygulamayla tarayın. Puanlama, yoklama eşleştirme ve madde analizi otomatik yapılsın.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-6 py-3 font-semibold text-indigo-950 bg-white rounded-md hover:bg-indigo-100">Panele Git</a>
                    @else
                        <a href="{{ route('register') }}" class="px-6 py-3 font-semibold text-indigo-950 bg-white rounded-md hover:bg-indigo-100">Hemen Başla</a>
                        <a href="{{ route('login') }}" class="px-6 py-3 font-semibold border border-indigo-400 rounded-md hover:bg-indigo-900">Giriş Yap</a>
                    @endauth
                </div>
                <div class="mt-8 flex gap-8 text-sm">
                    <div><div class="text-2xl font-bold">100</div><div class="text-indigo-300">soruya kadar form</div></div>
                    <div><div class="text-2xl font-bold">A–D</div><div class="text-indigo-300">kitapçık desteği</div></div>
                    <div><div class="text-2xl font-bold">Otomatik</div><div class="text-indigo-300">puan + analiz</div></div>
                </div>
            </div>
            <!-- Telefon mockup -->
            <div class="flex justify-center">
                <div class="w-64 rounded-[2.5rem] border-8 border-gray-800 bg-gray-100 overflow-hidden shadow-2xl">
                    <div class="bg-indigo-950 text-white text-center text-xs py-2 font-semibold">A kitapçığı tara</div>
                    <div class="relative bg-white m-3 rounded p-3">
                        <div class="absolute top-1 left-1 w-6 h-6 border-t-4 border-l-4 border-green-500"></div>
                        <div class="absolute top-1 right-1 w-6 h-6 border-t-4 border-r-4 border-green-500"></div>
                        <div class="absolute bottom-1 left-1 w-6 h-6 border-b-4 border-l-4 border-green-500"></div>
                        <div class="absolute bottom-1 right-1 w-6 h-6 border-b-4 border-r-4 border-green-500"></div>
                        <div class="text-[10px] font-bold text-center">MATEMATİK DENEME 1</div>
                        <div class="flex justify-center my-1">
                            <div class="w-10 h-10 bg-gray-900 rounded-sm"></div>
                        </div>
                        @for ($i = 1; $i <= 5; $i++)
                            <div class="flex items-center gap-1 my-1">
                                <span class="text-[9px] font-mono w-3">{{ $i }}</span>
                                @foreach (['A', 'B', 'C', 'D', 'E'] as $s)
                                    <span class="w-3 h-3 rounded-full border {{ $s === 'B' ? 'bg-gray-900 border-gray-900' : 'border-gray-500' }}"></span>
                                @endforeach
                            </div>
                        @endfor
                    </div>
                    <div class="mx-3 mb-3 bg-green-600 text-white text-xs rounded p-2 text-center font-semibold">Kaydedildi: 82,5 / 100</div>
                </div>
            </div>
        </div>
    </header>

    <!-- Nasıl çalışır -->
    <section id="nasil-calisir" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-extrabold text-center">3 adımda sonuç</h2>
            <div class="mt-10 grid md:grid-cols-3 gap-6">
                <div class="border rounded-xl p-6 shadow-sm">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-indigo-600 text-white font-bold">1</div>
                    <h3 class="mt-4 font-bold text-lg">Webde oluşturun</h3>
                    <p class="mt-2 text-gray-600 text-sm">Sınıf ve öğrencileri ekleyin, sınavı tanımlayın, her kitapçığın cevap anahtarını girin ve optik formu PDF olarak yazdırın.</p>
                </div>
                <div class="border rounded-xl p-6 shadow-sm">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-indigo-600 text-white font-bold">2</div>
                    <h3 class="mt-4 font-bold text-lg">Telefonda tarayın</h3>
                    <p class="mt-2 text-gray-600 text-sm">Hesabınızla giriş yapın, QR kodu okutarak formu doğrulayın, köşeleri hizalayıp çekin. Bağlantı yoksa kuyruğa alınır.</p>
                </div>
                <div class="border rounded-xl p-6 shadow-sm">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-indigo-600 text-white font-bold">3</div>
                    <h3 class="mt-4 font-bold text-lg">Sonuçları inceleyin</h3>
                    <p class="mt-2 text-gray-600 text-sm">Puanlar otomatik hesaplanır. Şüpheli kağıtlar inceleme kuyruğuna düşer, madde analiziyle zayıf soruları görün.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Özellikler -->
    <section id="ozellikler" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-extrabold text-center">Özellikler</h2>
            <div class="mt-10 grid md:grid-cols-3 gap-6 text-sm">
                @foreach ([
                    ['Çok kitapçıklı sınavlar', 'A, B, C, D kitapçıkları; her biri için ayrı cevap anahtarı ve QR kodlu form.'],
                    ['Öğrenci eşleştirme', '8 haneli numara baloncuğu sınıf listenizle otomatik eşleşir.'],
                    ['Çift işaret kontrolü', 'Çoklu karalama yanlış sayılmaz; inceleme kuyruğuna düşer, siz düzeltirsiniz.'],
                    ['Çevrimdışı tarama', 'İnternet yoksa çekimler kuyrukta bekler, bağlantı gelince gönderilir.'],
                    ['Madde analizi', 'Her sorunun doğru yüzdesi ve şık dağılımını görün.'],
                    ['Güvenli puanlama', 'Cevap anahtarı telefona inmez; puan sunucuda hesaplanır.'],
                ] as [$title, $desc])
                    <div class="bg-white border rounded-xl p-6 shadow-sm">
                        <h3 class="font-bold">{{ $title }}</h3>
                        <p class="mt-2 text-gray-600">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-20 bg-indigo-950 text-white text-center">
        <h2 class="text-3xl font-extrabold">İlk sınavınızı bugün oluşturun</h2>
        <p class="mt-3 text-indigo-200">Kayıt olun, sınıfınızı ekleyin, formu yazdırın.</p>
        <div class="mt-8">
            @auth
                <a href="{{ route('dashboard') }}" class="px-6 py-3 font-semibold text-indigo-950 bg-white rounded-md hover:bg-indigo-100">Panele Git</a>
            @else
                <a href="{{ route('register') }}" class="px-6 py-3 font-semibold text-indigo-950 bg-white rounded-md hover:bg-indigo-100">Ücretsiz Başla</a>
            @endauth
        </div>
    </section>

    <footer class="py-8 text-center text-sm text-gray-500 bg-white border-t">
        OptikReader — optik form okuma · <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Giriş Yap</a>
    </footer>
</body>
</html>
