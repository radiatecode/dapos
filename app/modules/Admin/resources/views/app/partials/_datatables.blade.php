@prepend('css')
    @include('admin::app.partials._datatable_css')
@endprepend

@prepend('js')
    @include('admin::app.partials._datatable_js')
    <script src="{{ asset('vendor/datatables/buttons.server-side.js') }}"></script>

    <script type="text/javascript">
        function dtFooter(table) {
            let state = LaravelDataTables[table].state.loaded();
            let columns = LaravelDataTables[table].init().columns;
            LaravelDataTables[table].columns().every(function(index) {
                let column = this;
                let title = columns[index].title;
                let input = document.createElement("input");
                input.size = 1;
                if (columns[index].searchable) {
                    $(input)
                        .attr('placeholder', title)
                        .attr('class', 'form-control')
                        .val(state ? state.columns[index].search.search : '')
                        .appendTo($(column.footer()).empty())
                        .on('change', function(event) {
                            event.target.size = event.target.value.length > 0 ? event.target.value.length : 1;
                            column.search($(this).val(), false, false, true).draw();
                        });
                }
            });
        }
    </script>
@endprepend
