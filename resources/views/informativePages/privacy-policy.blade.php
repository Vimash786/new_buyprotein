@extends('layouts.app')

@section('content')
    <div class="background-light-gray-color rts-section-gap bg_light-1 pt_sm--20">
        <div class="container">
            <h1>{{ $privacy_policy->title }}</h1>

            {!! $privacy_policy->content !!}
        </div>
    </div>
@endsection
