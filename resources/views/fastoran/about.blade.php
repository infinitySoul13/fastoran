@extends('layouts.app')

@section('content')
 @include("fastoran.partials.header")

 @include("fastoran.partials.ht__bradcaump__area",["title"=>"О Нас"])
 @include("fastoran.partials.food__about__us__area")

 @include("fastoran.partials.food__contact__form")
 @include("fastoran.partials.footer__area")
@endsection
