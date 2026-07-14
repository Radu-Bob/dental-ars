@extends($layout ?? 'layouts.app')

@section('title', 'Dental Data System Dashboard')

@section('left_content')
    <div class="bg-white p-6 rounded-2xl shadow-xl mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Clinic Calendar</h2>
        
        <div id="calendar-container"></div>

        @php
            $todaysAppointments = \App\Models\Appointment::with('patient')
                ->whereDate('appointment_date', today(config('app.clinic_timezone')))
                ->where('status', '!=', 'cancelled')
                ->orderBy('start_time')
                ->get();

            $dashCalDate = request('cal_date', today(config('app.clinic_timezone'))->format('Y-m-d'));
            $dashCalMonth = \Carbon\Carbon::parse($dashCalDate);
            $dashMonthDatesWithAppointments = \App\Models\Appointment::where('status', '!=', 'cancelled')
                ->whereBetween('appointment_date', [$dashCalMonth->copy()->startOfMonth()->format('Y-m-d'), $dashCalMonth->copy()->endOfMonth()->format('Y-m-d')])
                ->pluck('appointment_date')
                ->map(fn ($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
                ->unique()
                ->values();
        @endphp

        <div class="mt-4">
            <h3 id="apptPanelHeading" class="text-sm font-bold text-gray-700 border-b pb-2 mb-2">Today's Appointments</h3>
            <div id="apptPanelList">
                @if ($todaysAppointments->isEmpty())
                    <p class="text-sm text-gray-500 text-center">No appointments booked for today.</p>
                @else
                    <ul class="space-y-1.5 text-sm max-h-48 overflow-y-auto">
                        @foreach ($todaysAppointments as $appt)
                            <li class="flex justify-between items-center px-2 py-1 rounded hover:bg-gray-50">
                                <span class="text-gray-700">
                                    {{ \Carbon\Carbon::parse($appt->start_time)->format('H:i') }} –
                                    @if ($appt->patient)
                                        <a href="{{ route('patients.show', ['patient_id' => $appt->patient->patient_id]) }}" class="text-clinic hover:underline">{{ $appt->patient->name }}</a>
                                    @else
                                        {{ $appt->patient_name_freetext ?? '—' }}
                                    @endif
                                </span>
                                <span class="text-xs text-gray-400">{{ ucfirst(str_replace('_', ' ', $appt->status)) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <a id="apptViewAllLink" href="{{ route('appointments.index') }}" class="block text-center text-xs text-clinic hover:underline mt-2">
                View all appointments →
            </a>
        </div>
    </div>

    {{-- Quick Patient Search --}}
    <div class="bg-white p-4 rounded-2xl shadow-xl">
        <h2 class="text-sm font-bold text-gray-700 border-b pb-2 mb-3">Quick Search</h2>
        <form action="{{ route('patients.index') }}" method="GET">
            <div class="relative">
                <input type="text" name="q" placeholder="Patient name or ID…"
                       class="w-full border border-gray-300 rounded-lg pl-3 pr-9 py-2 text-sm focus:outline-none focus:ring-2"
                       autocomplete="off">
                <button type="submit"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-clinic transition duration-150"
                        tabindex="-1">
                    <i class="fas fa-search text-sm"></i>
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')

    <script src="{{ asset('js/vendor/moment.min.js') }}"></script>

    <script>
        // Start with the month being viewed (?cal_date=) or today
        let currentMonth = moment('{{ $dashCalDate }}');
        const dashMarkedDates = @json($dashMonthDatesWithAppointments);
        let dashSelectedDate = moment().format('YYYY-MM-DD');

        function renderCalendar(date) {
            const container = document.getElementById('calendar-container');
            if (!container) return;

            // ... (date logic remains the same) ...
            const startOfMonth = date.clone().startOf('month');
            const endOfMonth = date.clone().endOf('month');
            const startDay = startOfMonth.day();
            const today = moment().format('YYYY-MM-DD');

            let html = '<div class="calendar-nav flex justify-between items-center mb-3">';
            // Reduced size for buttons and headers
            html += '<button onclick="changeMonth(-1)" class="text-lg font-semibold p-1 hover:text-blue-600">&lt;</button>'; // Reduced from text-xl
            html += `<span class="text-base font-bold">${date.format('MMMM YYYY')}</span>`; // Reduced from text-lg
            html += '<button onclick="changeMonth(1)" class="text-lg font-semibold p-1 hover:text-blue-600">&gt;</button>'; // Reduced from text-xl
            html += '</div>';

            // Day names row (Start on Monday)
            html += '<div class="calendar-grid grid grid-cols-7 text-center text-xs font-medium text-gray-500 mb-1">'; // Reduced from text-sm
            ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'].forEach(day => {
                html += `<div>${day}</div>`;
            });
            html += '</div>';

            // The main grid for days
            html += '<div class="calendar-grid grid grid-cols-7 gap-1">';
            
            // ... (padding logic remains the same) ...
            let dayOfWeek = (startDay === 0) ? 6 : startDay - 1; 
            for (let i = 0; i < dayOfWeek; i++) {
                html += '<div></div>';
            }

            // Loop through the days of the month
            for (let day = 1; day <= endOfMonth.date(); day++) {
                const currentDateStr = startOfMonth.clone().add(day - 1, 'days').format('YYYY-MM-DD');
                
                //const isToday = currentDateStr === today ? 'bg-blue-600 text-white rounded-full' : 'hover:bg-gray-200';
                // We remove the bg-blue-600 and apply the color via an inline style attribute later
                const isSelected = currentDateStr === dashSelectedDate;
                let dayClasses = 'hover:bg-gray-200';
                let dayStyle = '';
                if (currentDateStr === today) {
                    dayClasses = 'text-white rounded-full';
                    dayStyle = 'style="background-color: {{ $themeColor }};"';
                }
                if (isSelected) {
                    dayClasses += ' ring-2 ring-offset-1';
                    dayStyle = 'style="background-color: {{ $themeColor }}; --tw-ring-color: {{ $themeColor }};' + (currentDateStr === today ? '' : ' color:#1f2937;') + '"';
                    if (currentDateStr !== today) dayClasses = dayClasses.replace('hover:bg-gray-200', 'bg-gray-100 font-bold');
                }
                const hasAppt = dashMarkedDates.includes(currentDateStr);
                const dot = hasAppt
                    ? `<span class="absolute bottom-0 w-1.5 h-1.5 rounded-full ${currentDateStr === today ? 'bg-white' : 'bg-orange-500'}"></span>`
                    : '';

                html += `<div class="w-8 h-8 flex items-center justify-center relative ${dayClasses} cursor-pointer text-xs mx-auto" ${dayStyle} onclick="selectAppointmentDay('${currentDateStr}')">${day}${dot}</div>`;
            }

            html += '</div>';
            container.innerHTML = html;
        }

        function selectAppointmentDay(dateStr) {
            dashSelectedDate = dateStr;
            renderCalendar(currentMonth);

            document.getElementById('apptViewAllLink').href = '{{ route("appointments.index") }}?date=' + dateStr;

            fetch('{{ route("appointments.day_summary") }}?date=' + dateStr)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('apptPanelHeading').textContent = data.date;
                    const listEl = document.getElementById('apptPanelList');

                    if (!data.items.length) {
                        listEl.innerHTML = '<p class="text-sm text-gray-500 text-center">No appointments booked for this day.</p>';
                        return;
                    }

                    let html = '<ul class="space-y-1.5 text-sm max-h-48 overflow-y-auto">';
                    data.items.forEach(function (item) {
                        const nameHtml = item.patient_id
                            ? `<a href="/patients/${item.patient_id}" class="text-clinic hover:underline">${item.name}</a>`
                            : item.name;
                        html += `<li class="flex justify-between items-center px-2 py-1 rounded hover:bg-gray-50">
                            <span class="text-gray-700">${item.time} – ${nameHtml}</span>
                            <span class="text-xs text-gray-400">${item.status}</span>
                        </li>`;
                    });
                    html += '</ul>';
                    listEl.innerHTML = html;
                });
        }

        // ... (changeMonth and initial call logic remains the same) ...
        function changeMonth(delta) {
            currentMonth.add(delta, 'months');
            window.location.href = '{{ route("dashboard") }}?cal_date=' + currentMonth.format('YYYY-MM-01');
        }

        document.addEventListener('DOMContentLoaded', function() {
            renderCalendar(currentMonth);
        });
    </script>
@endsection

@section('content')
    <h1 class="text-4xl font-extrabold text-gray-900 mb-8">Welcome, {{ Auth::user()->name }}!</h1>
    
    <p class="text-xl text-gray-600 mb-10">Your operational hub for managing patient records.</p>

    {{-- Big Buttons for Navigation --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <a href="{{ route('patients.index') }}" class="bg-white p-6 rounded-2xl shadow-xl hover:shadow-2xl transition duration-300 transform hover:scale-105 border-b-4 border-blue-500 block">
            <div class="text-blue-600 mb-3 text-3xl"><i class="fas fa-search"></i></div>
            <h2 class="text-2xl font-semibold text-gray-800">Search & List Patients</h2>
            <p class="text-gray-500 mt-2">Find existing records or view the full patient directory.</p>
        </a>

        <a href="{{ route('patients.register') }}" class="bg-white p-6 rounded-2xl shadow-xl hover:shadow-2xl transition duration-300 transform hover:scale-105 border-b-4 border-green-500 block">
            <div class="text-green-600 mb-3 text-3xl"><i class="fas fa-user-plus"></i></div>
            <h2 class="text-2xl font-semibold text-gray-800">Register New Patient</h2>
            <p class="text-gray-500 mt-2">Start a new record for a patient.</p>
        </a>

        <a href="{{ route('reports.index') }}" class="bg-white p-6 rounded-2xl shadow-xl hover:shadow-2xl transition duration-300 transform hover:scale-105 border-b-4 border-purple-500 block">
            <div class="text-purple-600 mb-3 text-3xl"><i class="fas fa-chart-line"></i></div>
            <h2 class="text-2xl font-semibold text-gray-800">View Reports</h2>
            <p class="text-gray-500 mt-2">Access statistical data and system reports.</p>
        </a>
    </div>

    {{-- *************************************************************** --}}
    {{-- NEW: Patient Attendance Audit (Last 6 Days) --}}
    {{-- *************************************************************** --}}
    @php
        // !!! WARNING: DATABASE QUERYING IN BLADE !!!
        // This is a temporary solution to meet the requirement without a dedicated controller.
        // It should be moved to a DashboardController as soon as possible.
        use Carbon\Carbon;
        use Illuminate\Support\Facades\DB;

        // 1. Define the 6-day window (7 days ago to include all of the 6th day)
        $sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();

        // 2. Query the database for records modified in the last 6 days.
        // --- REVISION 1: Corrected table name to patients_clinical ---
        // --- REVISION 2: Corrected column name to time_stamp ---
        $modifiedRecords = DB::table('patients_clinical')
            ->select('patients_clinical.patient_id', 'patients_clinical.time_stamp', 'patients.name')
            ->join('patients', 'patients_clinical.patient_id', '=', 'patients.patient_id')
            ->where('patients_clinical.time_stamp', '>=', $sevenDaysAgo)
            ->get();

        // 3. Organise the results into the required daily array structure, ensuring uniqueness.
        $dailyAttendance = [];
        $uniquePatientsPerDay = []; // Helper to track which patient_id has been added for a date

        // Initialize the last 6 days (1 to 6)
        for ($i = 1; $i <= 6; $i++) {
            $date = Carbon::now()->subDays($i);
            $dateKey = $date->format('Y-m-d'); // YYYY-MM-DD format
            $dailyAttendance[$dateKey] = [];
            $uniquePatientsPerDay[$dateKey] = [];
        }

        // Populate the structure with real, grouped data
        foreach ($modifiedRecords as $record) {
            // --- REVISION 3: Parsing the correct 'time_stamp' column ---
            $recordDate = Carbon::parse($record->time_stamp)->format('Y-m-d');

            // Check if the date falls within our initialized 6-day window
            if (isset($dailyAttendance[$recordDate])) {
                $patientId = $record->patient_id;
                
                // Ensure only one entry per patient_id per day
                if (!in_array($patientId, $uniquePatientsPerDay[$recordDate])) {
                    
                    $dailyAttendance[$recordDate][] = (object)[
                        'patient_id' => $patientId,
                        'name' => $record->name
                    ];
                    
                    // Mark this patient as added for today's date
                    $uniquePatientsPerDay[$recordDate][] = $patientId;
                }
            }
        }
        
        // 4. Prepare the final structure for rendering (Day 6, Day 5, 4, 3, 2, Day 1)
        $data = [];
        for ($i = 6; $i >= 1; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            
            // Get the array of patient objects from the calculated attendance
            $patients = $dailyAttendance[$dateKey] ?? [];

            $count = count($patients);

            $data[] = [
                'date' => $date->format('j M'),
                'label' => $date->isYesterday() ? 'Yesterday' : $date->format('l'),
                'patients' => $patients, // The actual patient data
                'count' => $count,
                'is_low' => $count < 3, 
                'is_high' => $count >= 5, 
            ];
        }

        $firstRow = array_slice($data, 0, 3); // Days 6, 5, 4 (Oldest)
        $secondRow = array_slice($data, 3, 3); // Days 3, 2, 1 (Most Recent)
    @endphp

    <h2 class="text-2xl font-bold text-gray-800 mt-12 mb-4">Unique Patients Seen (Last 6 Days)</h2>

    @php
        // Helper function to render each day's card (unchanged)
        $renderCard = function ($dayData) {
            $count = $dayData['count'];
            $bg = $dayData['is_high'] ? 'bg-red-500' : 'bg-white';
            $text = $dayData['is_high'] ? 'text-white' : 'text-gray-800';
            $shadow = $dayData['is_high'] ? 'shadow-lg shadow-red-300' : 'shadow-md';
            $patientList = '';

            // Build the list of linked patients
            if ($count > 0) {
                // Fixed height (h-28) and scrollable area for the list
                $patientList .= '<ul class="mt-2 space-y-1 text-sm h-28 overflow-y-auto pr-2">';
                foreach ($dayData['patients'] as $patient) {
                    $patientList .= '
                        <li class="' . ($dayData['is_high'] ? 'text-white/80 hover:text-white' : 'text-gray-600 hover:text-green-600') . ' truncate">
                            <a href="' . route('patients.show', ['patient_id' => $patient->patient_id]) . '" class="font-medium underline hover:no-underline transition duration-150">
                                ' . htmlspecialchars($patient->name) . '
                            </a>
                        </li>';
                }
                $patientList .= '</ul>';
            } else {
                 $patientList .= '<p class="mt-3 text-sm ' . ($dayData['is_high'] ? 'text-white/80' : 'text-gray-500') . '">No patients recorded for this day.</p>';
            }

            // Return the full HTML card (h-56 is roughly 224px high, perfect for the dashboard)
            return '
                <div class="p-4 rounded-xl ' . $bg . ' shadow-xl ' . $shadow . ' border border-gray-100 transition duration-200 flex flex-col h-56">
                    <p class="text-sm font-semibold ' . $text . ' mb-2 flex justify-between items-center">
                        <span>' . $dayData['label'] . ' - ' . $dayData['date'] . '</span>
                        <span class="text-4xl font-extrabold ' . ($dayData['is_high'] ? 'text-white' : 'text-blue-600') . '">' . $count . '</span>
                    </p>
                    <p class="text-xs font-medium ' . ($dayData['is_high'] ? 'text-white/80' : 'text-gray-500') . ' uppercase border-b pb-1">
                        Unique Patients
                    </p>
                    <div class="flex-grow min-h-0">
                        ' . $patientList . '
                    </div>
                </div>
            ';
        };
    @endphp

    {{-- 6-day attendance grid: hidden on mobile (not needed chair-side) --}}
    @if(!($isMobile ?? false))

    {{-- First Row: Day 6, Day 5, Day 4 (Oldest Data) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        @foreach ($firstRow as $day)
            {!! $renderCard($day) !!}
        @endforeach
    </div>

    {{-- Second Row: Day 3, Day 2, Day 1 (Most Recent Data) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($secondRow as $day)
            {!! $renderCard($day) !!}
        @endforeach
    </div>
    @endif {{-- end !$isMobile --}}

@endsection