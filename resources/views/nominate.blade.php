@extends('master')

@section('title', 'Nominera')

@section('head-extra')
<script type="text/javascript">
$(document).ready(function () {
    $('#email').on('input', function () {
        $("#year").show();
    });

    $('#name').autocomplete({
        source: function(request, response) {
            $.ajax({
                type: "GET",
                contentType: "application/json; charset=utf-8",
                url: "https://zfinger.datasektionen.se/users/" + request.term,
                dataType: "json",
                success: function (data) {
                    if (data != null) {
                        response(data.results.slice(0,8));
                    }
                },
                error: function(result) {
                    alert("Error");
                }
            });
        },
        minLength: 3,
        delay: 100,
        select: function(event, ui) {
            $("#email").val(ui.item.uid + "@kth.se");
            $("#name").val(ui.item.cn);
            if (ui.item.year)
                $("#year").val(ui.item.year);
            else 
                $("#year").hide();
            return false;
        },
        focus: function(event, ui) {
            $("#email").val(ui.item.uid + "@kth.se");
            $("#name").val(ui.item.cn);
            if (ui.item.year)
                $("#year").val(ui.item.year);
            else 
                $("#year").hide();
            return false;
        }
    }).data("ui-autocomplete")._renderItem = function(ul, item) {
        console.log(item);
        return $("<li></li>")
            .data("item.autocomplete", item)
            .append('<a><div class="crop" style="background-image:url(https://zfinger.datasektionen.se/user/' + item.uid + '/image/)"></div>'+ item.cn + " (" + item.uid + "@kth.se)</a>") 
            .appendTo(ul);
    };;
});
</script>
<style type="text/css">
    input.ui-autocomplete-loading {
        background-image: url(/images/loading.gif);
        background-size: 30px;
        background-repeat: no-repeat;
        background-position: center right;
    }
</style>
@endsection

@section('content')
@if (\App\Models\Election::nominateableElections()->count() == 0) 
<p>Det finns inga öppna val att nominera i.</p>
@else
{!! Form::open(['url' => URL::to(Request::path(), [], true)]) !!}
<div class="form">
    <div class="form-entry">
        <span class="description">
            Vem vill du nominera?<br>
            <span class="desc">Börja skriv ett namn så kommer en lista där du kan välja personer. Du kan också skriva manuellt. Kan du då inte allas KTH-mejl utantill? Kolla <a href="https://zfinger.datasektionen.se">Zfinger.</a></span>
        </span>
        <div class="input">
            {!! Form::text('name', NULL, array('placeholder' => 'Namn', 'id' => 'name')) !!}
            {!! Form::text('email', NULL, array('placeholder' => 'KTH-mejladress', 'id' => 'email')) !!}
            {!! Form::text('year', NULL, array('placeholder' => 'Årskurs', 'id' => 'year', 'class' => 'small')) !!}
        </div>
    </div>

    <div class="form-entry">
        <span class="description">
            Till vilka poster?
        </span>
        <div class="input">
            @foreach (\App\Models\Election::positionsForAllNominateableElections() as $position)
            <div class="checkbox">
                {{ Form::checkbox('positions[]', $position->identifier, false, array('id' => 'position-' . $position->identifier )) }} 
                <label for="position-{{ $position->identifier }}">{{ $position->title }}</label>
            </div>
            @endforeach
        </div>
    </div>

    @if (\App\Models\Election::nominateableElections()->count() > 1)
    <div class="form-entry">
        <span class="description">
            Val
        </span>
        <div class="input">
            <div class="select">
                {!! Form::select('election', \App\Models\Election::nominateableElections()->pluck('name', 'id')) !!}
            </div>
            <div class="clear"></div>
        </div>
    </div>
    @elseif (\App\Models\Election::nominateableElections()->count() === 1)
        <p>Du nominerar i valet {{ \App\Models\Election::nominateableElections()->first()->name }}.</p>
        {!! Form::hidden('election', \App\Models\Election::nominateableElections()->first()->id) !!}
    @else
        <p>Du nominerar inte i något val.</p>
        {!! Form::hidden('election', -1) !!}
    @endif

    <div class="form-entry">
        <div class="input">
            {!! Form::submit('Nominera', NULL) !!}
        </div>
    </div>
</div>
{!! Form::close() !!}
@endif
@endsection
