
@extends('dashboard.master')

@section('title', __('lang.company_name'))




@section('content')
        <div class="container">
            <h2 class="mb-4">Unite Offers</h2>
    <a href="{{ route('unite_offers.create') }}" class="btn btn-success mb-3">Add New Offer</a>

            @foreach ($unites as $type => $typedUnites)
                <div class="accordion mb-3" id="accordion-type-{{ $loop->index }}">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-type-{{ $loop->index }}">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse-type-{{ $loop->index }}" aria-expanded="false"
                                aria-controls="collapse-type-{{ $loop->index }}">
                                {{ ucfirst($type) }}
                            </button>
                        </h2>
                        <div id="collapse-type-{{ $loop->index }}" class="accordion-collapse collapse"
                            aria-labelledby="heading-type-{{ $loop->index }}" data-bs-parent="#accordion-type-{{ $loop->index }}">
                            <div class="accordion-body">

                                @forelse ($typedUnites as $unite)
                                    <div class="accordion mb-2" id="accordion-unite-{{ $unite->id }}">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading-unite-{{ $unite->id }}">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapse-unite-{{ $unite->id }}" aria-expanded="false"
                                                    aria-controls="collapse-unite-{{ $unite->id }}">
                                                    Unite: {{ $unite->name ?? 'N/A' }}
                                                </button>
                                            </h2>
                                            <div id="collapse-unite-{{ $unite->id }}" class="accordion-collapse collapse"
                                                aria-labelledby="heading-unite-{{ $unite->id }}"
                                                data-bs-parent="#accordion-unite-{{ $unite->id }}">
                                                <div class="accordion-body">

                                                    @if ($unite->offers->isNotEmpty())
                                                        <table class="table table-bordered">
                                                            <thead>
                                                                <tr>
                                                                    <th>Start</th>
                                                                    <th>End</th>
                                                                    <th>Morning Price</th>
                                                                    <th>Evening Price</th>
                                                                    <th>Full Day Price</th>
                                                                    <th>Status</th>
                                                                    <th>Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($unite->offers as $offer)
                                                                    <tr>
                                                                        <td>{{ $offer->start }}</td>
                                                                        <td>{{ $offer->end }}</td>
                                                                        <td>{{ $offer->morning_price }}</td>
                                                                        <td>{{ $offer->evening_price }}</td>
                                                                        <td>{{ $offer->full_day_price }}</td>
                                                                        <td>
                                                                            <span
                                                                                class="badge bg-{{ $offer->status == 'active' ? 'success' : 'secondary' }}">
                                                                                {{ ucfirst($offer->status) }}
                                                                            </span>
                                                                        </td>
                                                                    <td>
                                                                        <a href="{{ route('unite_offers.show', $offer->id) }}" class="btn btn-sm btn-info">View</a>
                                                                        <a href="{{ route('unite_offers.edit', $offer->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                                                        <form action="{{ route('unite_offers.destroy', $offer->id) }}" method="POST" style="display:inline;">
                                                                            @csrf @method('DELETE')
                                                                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                                                        </form>
                                                                    </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    @else
                                                        <p class="text-muted">No offers for this unite.</p>
                                                    @endif

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted">No unites under this type.</p>
                                @endforelse

                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
@endsection
