{{-- Reusable heatmap partial
     Expected vars: $crossTab (array with rows, cols, matrix, maxValue), $title, $description --}}

@if (!empty($crossTab['rows']) && !empty($crossTab['cols']))
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
            @if (!empty($description))
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $description }}</p>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700 min-w-[140px]"></th>
                        @foreach ($crossTab['cols'] as $col)
                            <th class="px-2 py-2 text-center font-medium text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 whitespace-nowrap max-w-[120px] truncate" title="{{ $col }}">
                                {{ \Illuminate\Support\Str::limit($col, 18) }}
                            </th>
                        @endforeach
                        <th class="px-2 py-2 text-center font-semibold text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($crossTab['rows'] as $rowIdx => $row)
                        @php
                            $rowTotal = array_sum($crossTab['matrix'][$rowIdx]);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-3 py-2 font-medium text-gray-700 dark:text-gray-300 border-b border-gray-100 dark:border-gray-700 whitespace-nowrap">
                                {{ $row }}
                            </td>
                            @foreach ($crossTab['matrix'][$rowIdx] as $colIdx => $value)
                                @php
                                    $opacity = $crossTab['maxValue'] > 0 ? round(($value / $crossTab['maxValue']) * 0.85 + 0.05, 2) : 0;
                                    $textClass = $opacity > 0.5 ? 'text-white' : 'text-gray-700 dark:text-gray-300';
                                @endphp
                                <td class="px-2 py-2 text-center border-b border-gray-100 dark:border-gray-700 {{ $textClass }}"
                                    style="{{ $value > 0 ? 'background-color: rgba(62, 155, 144, ' . $opacity . ')' : '' }}"
                                    title="{{ $row }} / {{ $crossTab['cols'][$colIdx] }}: {{ $value }}">
                                    {{ $value > 0 ? $value : '' }}
                                </td>
                            @endforeach
                            <td class="px-2 py-2 text-center font-semibold text-gray-700 dark:text-gray-300 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                {{ $rowTotal }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-700/50 px-3 py-2 font-semibold text-gray-700 dark:text-gray-300">Total</td>
                        @foreach ($crossTab['cols'] as $colIdx => $col)
                            @php
                                $colTotal = 0;
                                foreach ($crossTab['matrix'] as $matrixRow) {
                                    $colTotal += $matrixRow[$colIdx] ?? 0;
                                }
                            @endphp
                            <td class="px-2 py-2 text-center font-semibold text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50">
                                {{ $colTotal }}
                            </td>
                        @endforeach
                        <td class="px-2 py-2 text-center font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-600/50">
                            @php
                                $grandTotal = 0;
                                foreach ($crossTab['matrix'] as $matrixRow) {
                                    $grandTotal += array_sum($matrixRow);
                                }
                            @endphp
                            {{ $grandTotal }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endif
