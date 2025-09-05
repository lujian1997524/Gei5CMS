<!-- 文本输入字段 -->
<div class="mb-3">
    <label for="{{ $field['name'] }}" class="form-label">
        {{ $field['label'] }}
        @if($field['required'] ?? false)
            <span class="text-danger">*</span>
        @endif
    </label>
    
    <input 
        type="text" 
        class="form-control @error($field['name']) is-invalid @enderror" 
        id="{{ $field['name'] }}" 
        name="{{ $field['name'] }}" 
        value="{{ old($field['name'], $item->{$field['name']} ?? '') }}"
        @if($field['required'] ?? false) required @endif
        @if(!empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif
        @if(!empty($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif
    >
    
    @if(!empty($field['help']))
        <div class="field-help">{{ $field['help'] }}</div>
    @endif
    
    @error($field['name'])
        <div class="field-error">{{ $message }}</div>
    @enderror
</div>