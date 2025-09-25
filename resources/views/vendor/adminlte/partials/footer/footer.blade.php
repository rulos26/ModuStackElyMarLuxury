@php
    $footerService = app(\App\Services\FooterService::class);
    $footerConfig = $footerService->getFooterConfig();
@endphp

@if($footerConfig['use_custom_html'] && !empty($footerConfig['custom_html']))
    {!! $footerConfig['custom_html'] !!}
@else
    <footer class="main-footer">
        @if($footerConfig['show_center_text'] && !empty($footerConfig['center_text']))
            <div class="text-center">
                <strong>{!! $footerConfig['center_text'] !!}</strong>
            </div>
        @else
            {{-- Layout tradicional con left y right --}}
            @if($footerConfig['show_copyright'])
                <div class="float-right d-none d-sm-inline">
                    <strong>Copyright &copy; {{ date('Y') }}
                        <a href="{{ $footerConfig['company_url'] !== '#' ? $footerConfig['company_url'] : '#' }}">
                            {{ $footerConfig['company_name'] }}
                        </a>.
                    </strong>
                    Todos los derechos reservados.
                </div>
            @endif

            <div class="float-left d-none d-sm-inline">
                @if($footerConfig['show_version'])
                    <strong>Versión</strong> {{ $footerConfig['version_text'] }}
                @endif
                @if(!empty($footerConfig['left_text']))
                    {!! $footerConfig['left_text'] !!}
                @endif
            </div>
        @endif
        <div class="clearfix"></div>
    </footer>
@endif
