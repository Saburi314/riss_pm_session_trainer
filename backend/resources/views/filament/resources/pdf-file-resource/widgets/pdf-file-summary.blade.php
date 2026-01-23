@php
    $data = $this->getData();
    $matrix = $data['matrix'];
    $years = $data['years'];
    $seasons = $data['seasons'];
    $eraMapper = $data['eraMapper'];
    $notesMapper = $data['notesMapper'];
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            PDF登録状況マトリックス
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="px-4 py-2 font-medium">年度 (和暦)</th>
                        @foreach($seasons as $key => $label)
                            <th class="px-4 py-2 font-medium text-center">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @foreach($years as $year)
                        <tr>
                            <td class="px-4 py-2">
                                <span class="font-bold whitespace-nowrap">{{ $year }}年</span>
                                <span class="text-xs text-gray-500 block whitespace-nowrap">({{ $eraMapper($year) }})</span>
                            </td>
                            @foreach($seasons as $seasonKey => $seasonLabel)
                                @php
                                    $count = $matrix[$year][$seasonKey] ?? 0;
                                    $note = $notesMapper($year, $seasonKey);
                                    $colorClass = $count > 0 ? 'text-success-600 dark:text-success-400 font-bold' : ($note ? 'text-gray-400 dark:text-gray-500' : 'text-danger-600 dark:text-danger-400 opacity-50');
                                @endphp
                                <td class="px-4 py-2 text-center {{ $colorClass }}">
                                    @if($count > 0)
                                        <div class="flex flex-col items-center">
                                            <span>{{ $count }}件</span>
                                            @if($note)
                                                <span class="text-[10px] font-normal leading-tight text-gray-500 mt-1 max-w-[120px]">{{ $note }}</span>
                                            @endif
                                        </div>
                                    @elseif($note)
                                        <span class="text-[10px] leading-tight">{{ $note }}</span>
                                    @else
                                        なし
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="mt-4 space-y-1">
            <p class="text-xs text-gray-500">
                ※ 「なし」と表示されている箇所は、その年度・時期のファイルがデータベースに登録されていない可能性があります。
            </p>
            <p class="text-xs text-gray-500">
                ※ 2023年秋期以降は、午後I・II統合により1試験あたり合計5ファイル（午前Ⅱ（問題冊子、解答例、採点講評）, 午後（問題冊子、解答例、採点講評））の構成に変わっています。
            </p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>