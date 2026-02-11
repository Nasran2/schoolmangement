<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Reports</h2>
                <p class="text-sm text-gray-600 mt-1">Browse by category, then export as PDF/CSV/Excel</p>
            </div>
            <div class="hidden sm:flex gap-2">
                <a href="{{ route('reports.exports') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition">Advanced Exports</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @php
                        $card = 'group relative overflow-hidden rounded-xl border bg-white p-5 transition-all duration-200 ease-out hover:-translate-y-0.5 hover:shadow-lg';
                        $bar = 'absolute inset-x-0 top-0 h-1 bg-gradient-to-r';
                        $title = 'font-semibold text-gray-900';
                        $desc = 'text-sm text-gray-600 mt-1';
                    @endphp

                    <div class="space-y-8">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div class="text-sm font-semibold text-gray-800">Exports</div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                @can('reports.download')
                                    <a href="{{ route('reports.exports') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-indigo-500 to-blue-500"></div>
                                        <div class="{{ $title }}">Advanced Exports</div>
                                        <div class="{{ $desc }}">CSV/Excel exports + download all PDFs as a bundle.</div>
                                    </a>
                                @endcan
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-semibold text-gray-800 mb-3">Finance</div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                @can('reports.revenue.view')
                                    <a href="{{ route('reports.revenue') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-emerald-500 to-green-500"></div>
                                        <div class="{{ $title }}">Revenue Report</div>
                                        <div class="{{ $desc }}">Filter by date range and category.</div>
                                    </a>
                                @endcan
                                @can('reports.expense.view')
                                    <a href="{{ route('reports.expense') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-rose-500 to-red-500"></div>
                                        <div class="{{ $title }}">Expense Report</div>
                                        <div class="{{ $desc }}">Filter by date range and category (optional salary inclusion).</div>
                                    </a>
                                @endcan
                                @can('reports.outflows.view')
                                    <a href="{{ route('reports.outflows') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-violet-500 to-indigo-500"></div>
                                        <div class="{{ $title }}">All Outflows</div>
                                        <div class="{{ $desc }}">Expenses + salary + teacher payouts (no double-counting).</div>
                                    </a>
                                @endcan
                                @can('reports.financial.view')
                                    <a href="{{ route('reports.financial') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-sky-500 to-blue-500"></div>
                                        <div class="{{ $title }}">Financial Summary</div>
                                        <div class="{{ $desc }}">Revenue vs expenses and net profit.</div>
                                    </a>
                                @endcan
                                @can('reports.daily_ledger.view')
                                    <a href="{{ route('reports.daily_ledger') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-amber-500 to-orange-500"></div>
                                        <div class="{{ $title }}">Daily Ledger</div>
                                        <div class="{{ $desc }}">Opening → revenues → expenses → closing.</div>
                                    </a>
                                @endcan
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-semibold text-gray-800 mb-3">Transactions</div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                @can('reports.cash_transactions.view')
                                    <a href="{{ route('reports.cash_transactions') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-gray-600 to-gray-800"></div>
                                        <div class="{{ $title }}">Cash Transactions</div>
                                        <div class="{{ $desc }}">Cash in/out (includes refunds as outflow).</div>
                                    </a>
                                @endcan
                                @can('reports.bank_transactions.view')
                                    <a href="{{ route('reports.bank_transactions') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-blue-600 to-indigo-700"></div>
                                        <div class="{{ $title }}">Bank Transactions</div>
                                        <div class="{{ $desc }}">Bank transfer + cheque in/out.</div>
                                    </a>
                                @endcan
                                @can('reports.cheque_history.view')
                                    <a href="{{ route('reports.cheque_history') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-teal-500 to-cyan-600"></div>
                                        <div class="{{ $title }}">Cheque History</div>
                                        <div class="{{ $desc }}">Cheque details with in/out.</div>
                                    </a>
                                @endcan
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-semibold text-gray-800 mb-3">Payroll & Statutory</div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                @can('reports.teacher_epf.view')
                                    <a href="{{ route('reports.teacher_epf') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-emerald-500 to-teal-500"></div>
                                        <div class="{{ $title }}">Teacher EPF Report</div>
                                        <div class="{{ $desc }}">Employee EPF deductions from salary payments.</div>
                                    </a>
                                @endcan
                                @can('reports.company_epf.view')
                                    <a href="{{ route('reports.company_epf') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-emerald-500 to-teal-500"></div>
                                        <div class="{{ $title }}">Company EPF Report</div>
                                        <div class="{{ $desc }}">Company EPF contribution from salary payments.</div>
                                    </a>
                                @endcan
                                @can('reports.teacher_etf.view')
                                    <a href="{{ route('reports.teacher_etf') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-emerald-500 to-teal-500"></div>
                                        <div class="{{ $title }}">Company ETF Report</div>
                                        <div class="{{ $desc }}">Company ETF contribution from salary payments.</div>
                                    </a>
                                @endcan
                                @can('reports.epf_etf_totals.view')
                                    <a href="{{ route('reports.epf_etf_totals') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-emerald-500 to-teal-500"></div>
                                        <div class="{{ $title }}">EPF/ETF Totals</div>
                                        <div class="{{ $desc }}">Combined totals for EPF + ETF.</div>
                                    </a>
                                @endcan
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-semibold text-gray-800 mb-3">Students & Fees</div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                @can('reports.student_due.view')
                                    <a href="{{ route('reports.student_due') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-fuchsia-500 to-pink-500"></div>
                                        <div class="{{ $title }}">Student Due Amount</div>
                                        <div class="{{ $desc }}">Monthly fee due amounts by student/class.</div>
                                    </a>
                                @endcan
                                @can('reports.fee_collection_summary.view')
                                    <a href="{{ route('reports.fee_collection_summary') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-indigo-500 to-purple-500"></div>
                                        <div class="{{ $title }}">Fee Collection Summary</div>
                                        <div class="{{ $desc }}">Totals grouped by day or month.</div>
                                    </a>
                                @endcan
                                @can('reports.fee_collection_by_class.view')
                                    <a href="{{ route('reports.fee_collection_by_class') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-indigo-500 to-purple-500"></div>
                                        <div class="{{ $title }}">Fee Collection by Class</div>
                                        <div class="{{ $desc }}">Total collection grouped by class room.</div>
                                    </a>
                                @endcan
                                @can('reports.fee_collection_by_category.view')
                                    <a href="{{ route('reports.fee_collection_by_category') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-indigo-500 to-purple-500"></div>
                                        <div class="{{ $title }}">Fee Collection by Category</div>
                                        <div class="{{ $desc }}">Collection totals grouped by revenue category.</div>
                                    </a>
                                @endcan
                                @can('reports.fee_collection_vs_expected.view')
                                    <a href="{{ route('reports.fee_collection_vs_expected') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-indigo-500 to-purple-500"></div>
                                        <div class="{{ $title }}">Collected vs Expected</div>
                                        <div class="{{ $desc }}">Month-wise expected vs collected.</div>
                                    </a>
                                @endcan
                                @can('reports.student_due_aging.view')
                                    <a href="{{ route('reports.student_due_aging') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-fuchsia-500 to-pink-500"></div>
                                        <div class="{{ $title }}">Student Due Aging</div>
                                        <div class="{{ $desc }}">Bucket students by pending months and total due.</div>
                                    </a>
                                @endcan
                                @can('reports.student_top_due.view')
                                    <a href="{{ route('reports.student_top_due') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-fuchsia-500 to-pink-500"></div>
                                        <div class="{{ $title }}">Top Due Students</div>
                                        <div class="{{ $desc }}">Highest due students for follow-up.</div>
                                    </a>
                                @endcan
                                @can('reports.fee_discounts.view')
                                    <a href="{{ route('reports.fee_discounts') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-slate-500 to-gray-700"></div>
                                        <div class="{{ $title }}">Discount/Waiver Report</div>
                                        <div class="{{ $desc }}">Requires discount tracking fields.</div>
                                    </a>
                                @endcan
                                @can('reports.fee_refunds.view')
                                    <a href="{{ route('reports.fee_refunds') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-slate-500 to-gray-700"></div>
                                        <div class="{{ $title }}">Refund/Cancellation Report</div>
                                        <div class="{{ $desc }}">Requires refund/cancellation fields.</div>
                                    </a>
                                @endcan
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-semibold text-gray-800 mb-3">Programs</div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                @can('reports.seminars_collection.view')
                                    <a href="{{ route('reports.seminars_collection') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-cyan-500 to-blue-500"></div>
                                        <div class="{{ $title }}">Seminars Collection</div>
                                        <div class="{{ $desc }}">Attendance & payments summary for seminars.</div>
                                    </a>
                                @endcan
                                @can('reports.extra_classes_collection.view')
                                    <a href="{{ route('reports.extra_classes_collection') }}" class="{{ $card }}">
                                        <div class="{{ $bar }} from-cyan-500 to-blue-500"></div>
                                        <div class="{{ $title }}">Extra Classes Collection</div>
                                        <div class="{{ $desc }}">Payments summary for extra classes.</div>
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
