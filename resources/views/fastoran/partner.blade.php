@extends('layouts.app')

@section('content')
    @include("fastoran.partials.header")

    @include("fastoran.partials.ht__bradcaump__area",["title"=>"Нашим партнерам"])
    @include("fastoran.partials.footer__area")
@endsection
