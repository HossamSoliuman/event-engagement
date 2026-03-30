<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
    <div class="position-sticky">
        <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <form action="{{route('logout')}}" method="post">
                    @csrf
                    <button type="submit" class="nav-link btn btn-danger btn-block mb-2" >
                        Logout
                    </button>
                    </a>

                </form>
            </li>

        </ul>
    </div>
</nav>
