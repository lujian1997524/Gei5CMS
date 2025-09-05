<!-- 选择框字段 -->
<div class="mb-3">
    <label for="{{ $field['name'] }}" class="form-label">
        {{ $field['label'] }}
        @if($field['required'] ?? false)
            <span class="text-danger">*</span>
        @endif
    </label>
    
    <select 
        class="form-select @if($field['select2'] ?? false) select2 @endif @error($field['name']) is-invalid @enderror" 
        id="{{ $field['name'] }}" 
        name="{{ $field['name'] }}"
        @if($field['required'] ?? false) required @endif
        @if($field['multiple'] ?? false) multiple @endif
    >
        @if(!($field['required'] ?? false) && !($field['multiple'] ?? false))
            <option value="">{{ $field['placeholder'] ?? '请选择' }}</option>
        @endif
        
        @if(!empty($field['options']))
            @foreach($field['options'] as $value => $label)
                <option 
                    value="{{ $value }}" 
                    @if(
                        old($field['name']) == $value || 
                        (is_null(old($field['name'])) && ($item->{$field['name']} ?? '') == $value)
                    ) selected @endif
                >
                    {{ $label }}
                </option>
            @endforeach
        @endif
    </select>
    
    @if(!empty($field['help']))
        <div class="field-help">{{ $field['help'] }}</div>
    @endif
    
    @error($field['name'])
        <div class="field-error">{{ $message }}</div>
    @enderror
</div>