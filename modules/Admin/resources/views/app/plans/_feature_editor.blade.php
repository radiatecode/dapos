<div class="plan-feature-editor mt-4">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Module Features</h5>
                    <div class="custom-control custom-checkbox admin-check mb-0">
                        <input class="custom-control-input js-feature-check-all" type="checkbox"
                            id="feature_check_all_module">
                        <label class="custom-control-label" for="feature_check_all_module">Check
                            all</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table admin-table plan-feature-table mb-0">
                            <tbody>
                                @foreach ($moduleFeatures as $feature)
                                    <tr class="plan-feature-row">
                                        <td>
                                            <div class="custom-control custom-checkbox admin-check">
                                                <input class="custom-control-input js-feature-assigned" type="checkbox"
                                                    id="feature_{{ $feature->value }}" name="features[]"
                                                    value="{{ $feature->value }}"
                                                    {{ in_array($feature->value, old('features', $stored_features)) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="feature_{{ $feature->value }}">
                                                    <strong class="plan-feature-name">{{ $feature->label() }}</strong>
                                                    <div class="plan-feature-code">{{ $feature->value }}</div>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Report Features</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table admin-table plan-feature-table mb-0">
                            <tbody>
                                @foreach ($reportFeatures as $feature)
                                    <tr class="plan-feature-row">
                                        <td>
                                            <div class="custom-control custom-checkbox admin-check">
                                                <input class="custom-control-input js-feature-assigned" type="checkbox"
                                                    id="feature_{{ $feature->value }}" name="features[]"
                                                    value="{{ $feature->value }}"
                                                    {{ in_array($feature->value, old('features', $stored_features)) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="feature_{{ $feature->value }}">
                                                    <strong class="plan-feature-name">{{ $feature->label() }}</strong>
                                                    <div class="plan-feature-code">{{ $feature->value }}</div>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Limit Features</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table admin-table plan-feature-table mb-0">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th>
                                        Value
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($limitFeatures as $feature)
                                    @php
                                        $dbFeature = array_values(
                                            array_filter($stored_limit_features, function ($item) use ($feature) {
                                                return $item['feature'] === $feature->value;
                                            }),
                                        );

                                        $value = isset($dbFeature[0]['value']) ? (int) $dbFeature[0]['value'] : 0;
                                        $isUnlimited = isset($dbFeature[0]['is_unlimited'])
                                            ? (bool) $dbFeature[0]['is_unlimited']
                                            : false;
                                    @endphp
                                    <tr class="plan-feature-row">
                                        <td>
                                            <div class="custom-control custom-checkbox admin-check">
                                                <input class="custom-control-input js-feature-assigned" type="checkbox"
                                                    id="feature_{{ $feature->value }}"
                                                    name="limit_features[{{ $feature->value }}][assigned]"
                                                    value="{{ $feature->value }}" {{ $dbFeature ? 'checked' : '' }}>
                                                <label class="custom-control-label"
                                                    for="feature_{{ $feature->value }}">
                                                    <strong class="plan-feature-name">{{ $feature->label() }}</strong>
                                                    <div class="plan-feature-code">{{ $feature->value }}</div>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control admin-input js-feature-value"
                                                name="limit_features[{{ $feature->value }}][value]"
                                                value="{{ $value }}">
                                            <input type="checkbox"
                                                name="limit_features[{{ $feature->value }}][unlimited]" value="1"
                                                {{ $isUnlimited ? 'checked' : '' }}>
                                            <strong class="plan-feature-name">Unlimited</strong>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
