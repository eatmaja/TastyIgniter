@php
    $type = $tabs->section;
    $activeTab = $activeTab ? $activeTab : '#'.$type.'tab-1';
@endphp
<div class="tab-heading">
    <ul class="form-nav nav nav-tabs">
        @php
            $index = 0;
        @endphp
        @foreach ($tabs as $name => $fields)
            @php
                $index++;
                $tabName = '#'.$type.'tab-'.$index;
            @endphp
            <li class="nav-item">
                <a
                    class="nav-link{{ ($tabName == $activeTab) ? ' active' : '' }}"
                    href="{{ $tabName }}"
                    data-toggle="tab"
                >@lang($name)</a>
            </li>
        @endforeach
    </ul>
</div>

<div class="row no-gutters">
    <div class="col-md-8">
        <div class="tab-content">
            @php
                $index = 0;
            @endphp
            @foreach ($tabs as $name => $fields)
                @php
                    $index++;
                    $tabName = '#'.$type.'tab-'.$index;
                @endphp
                <div
                    class="tab-pane {{ ($tabName == $activeTab) ? 'active' : '' }}"
                    id="{{ $type.'tab-'.$index }}">
                    <div class="form-fields">
                        {!! $this->makePartial('form/form_fields', ['fields' => $fields]) !!}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="col-md-4">
        {!! $this->makePartial('mailtemplates/form/variables', [
            'cssClass' => ' form-fields pl-0',
            'variables' => \System\Classes\MailManager::instance()->listRegisteredVariables(),
        ]) !!}
    </div>
</div>
