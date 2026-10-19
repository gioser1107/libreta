@foreach (\App\Models\Expense::groupedCategories() as $group => $options)
    <optgroup label="{{ $group }}">
        @foreach ($options as $key => $label)
            <option value="{{ $key }}">{{ $label }}</option>
        @endforeach
    </optgroup>
@endforeach
